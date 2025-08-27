<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use Exception;
use PDO;

class ProductVariantController
{
    private AdminService $adminService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->adminService = new AdminService();
        $this->adminService->checkAdminRole();
    }

    public function index(): void
    {
        $productId = $_GET['id'] ?? 0;
        if (!$productId) {
            header('Location: /admin/products');
            exit;
        }

        $db = $this->adminService->getDatabase();
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        try {
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $_SESSION['message'] = 'Produkt nenalezen';
                header('Location: /admin/products');
                exit;
            }

            try {
                $stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_code ASC");
                $stmt->execute([$productId]);
                $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                try {
                    $stmt = $db->prepare("SELECT * FROM items WHERE parent_item_id = ? ORDER BY variant_name ASC");
                    $stmt->execute([$productId]);
                    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($variants as &$variant) {
                        $variant['variant_code'] = $variant['variant_name'] ?? '';
                    }
                } catch (Exception $e2) {
                    $variants = [];
                }
            }

        } catch (Exception $e) {
            $message = 'Chyba při načítání variant: ' . $e->getMessage();
            $product = [];
            $variants = [];
        }

        $this->render('admin/products/variants/index', [
            'product' => $product,
            'variants' => $variants,
            'message' => $message,
            'adminService' => $this->adminService
        ]);
    }

    public function add(): void
    {
        $productId = $_GET['product_id'] ?? 0;
        if (!$productId) {
            header('Location: /admin/products');
            exit;
        }

        $db = $this->adminService->getDatabase();
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        try {
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $_SESSION['message'] = 'Produkt nenalezen';
                header('Location: /admin/products');
                exit;
            }

            $existingVariants = [];
            $usedCodes = [];
            try {
                $stmt = $db->prepare("SELECT * FROM items WHERE parent_item_id = ?");
                $stmt->execute([$productId]);
                $existingVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $usedCodes = array_column($existingVariants, 'variant_name');
            } catch (Exception $e) {
                $existingVariants = [];
                $usedCodes = [];
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $variantCode = strtoupper(trim($_POST['variant_code'] ?? ''));
                    if (empty($variantCode)) {
                        throw new Exception("Kód varianty je povinný!");
                    }
                    if (in_array($variantCode, $usedCodes)) {
                        throw new Exception("Kód varianty '{$variantCode}' už existuje!");
                    }

                    $variantName = $_POST['variant_name'] ?? $product['name'] . ' - ' . $variantCode;
                    $variantPrice = $_POST['price'] ?? $product['price'];
                    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
                    
                    $stmt = $db->prepare("
                        INSERT INTO items (
                            name, product_code, price, price_without_dph, 
                            parent_item_id, variant_name, visible, 
                            category, stock, description_main, image, image_folder, mass
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $priceWithoutDph = $variantPrice * 0.79; 
                    $stockStatus = $stockQuantity > 0 ? 'Skladem' : 'Není skladem';
                    
                    $success = $stmt->execute([
                        $variantName,
                        $product['product_code'] . '-' . $variantCode,
                        $variantPrice,
                        $priceWithoutDph,
                        $productId,
                        $variantCode,
                        1, 
                        $product['category'],
                        $stockStatus,
                        $_POST['description'] ?? '',
                        $product['image'], 
                        $product['image_folder'],
                        $product['mass']
                    ]);
                    
                    if ($success) {
                        $_SESSION['message'] = "Varianta '{$variantCode}' byla úspěšně přidána!";
                        header("Location: /admin/products/variants?id={$productId}");
                        exit;
                    } else {
                        throw new Exception("Chyba při ukládání varianty.");
                    }

                } catch (Exception $e) {
                    $message = 'Chyba: ' . $e->getMessage();
                }
            }

            $suggestedCodes = $this->generateSuggestedCodes($usedCodes);

        } catch (Exception $e) {
            $message = 'Chyba při načítání dat: ' . $e->getMessage();
            $product = [];
            $existingVariants = [];
            $suggestedCodes = ['A', 'B', 'C'];
        }

        $this->render('admin/products/variants/add', [
            'product' => $product,
            'existingVariants' => $existingVariants,
            'suggestedCodes' => $suggestedCodes,
            'message' => $message,
            'adminService' => $this->adminService
        ]);
    }

    public function edit(): void
    {
        $variantId = $_GET['id'] ?? 0;
        $variant = $this->variantModel->getVariantById($variantId);

        if (!$variant) {
            $this->setErrorMessage("Varianta nenalezena.");
            $this->redirect('/admin/products');
        }

        $productId = $variant['parent_item_id'];
        $product = $this->productModel->getById($productId);

        if (!$product) {
            $this->setErrorMessage("Hlavní produkt neexistuje.");
            $this->redirect('/admin/products');
        }

        $existingVariants = $this->variantModel->getVariantsByProductId($productId);
        $usedCodes = array_column($existingVariants, 'variant_name');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] == 10) {
            try {
                $variantCode = strtoupper(trim($_POST['variant_code']));
                if ($variantCode !== $variant['variant_name'] && in_array($variantCode, $usedCodes)) {
                    throw new Exception("Kód varianty '{$variantCode}' už existuje!");
                }

                $primaryImage = $variant['primary_image'] ?? '';
                if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                    $newImage = $this->handleImageUpload($_FILES['primary_image'], $productId);
                    if (!empty($primaryImage)) {
                        $this->deleteImage($primaryImage);
                    }
                    $primaryImage = $newImage;
                }

                $variantData = [
                    'variant_name' => $variantCode,
                    'variant_value' => $_POST['variant_description'] ?? '',
                    'price_modifier' => !empty($_POST['price_modifier']) ? (float)$_POST['price_modifier'] : 0,
                    'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                    'is_active' => 1
                ];

                $success = $this->variantModel->updateVariant($variantId, $variantData);
                
                if ($success) {
                    $this->setSuccessMessage("Varianta '{$variantCode}' byla úspěšně aktualizována!");
                    $variant = $this->variantModel->getVariantById($variantId); 
                } else {
                    throw new Exception("Chyba při aktualizaci varianty.");
                }

            } catch (Exception $e) {
                $this->setErrorMessage($e->getMessage());
            }
        }

        $this->render('admin/products/edit_variant', [
            'product' => $product,
            'variant' => $variant
        ]);
    }

    public function delete(): void
    {
        $variantId = $_GET['id'] ?? 0;
        $productId = $_GET['product_id'] ?? null;

        $variant = $this->variantModel->getVariantById($variantId);
        if (!$variant) {
            $this->setErrorMessage("Varianta nenalezena.");
            $this->redirect('/admin/products');
        }

        if (!$productId) {
            $productId = $variant['parent_item_id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('admin/products/delete_variant', [
                'variant' => $variant,
                'productId' => $productId
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] == 10) {
            try {
                $success = $this->variantModel->deleteVariant($variantId);
                
                if ($success) {
                    $this->setSuccessMessage("Varianta '{$variant['variant_name']}' byla úspěšně smazána.");
                } else {
                    $this->setErrorMessage("Chyba při mazání varianty.");
                }
                
            } catch (Exception $e) {
                $this->setErrorMessage($e->getMessage());
            }
            
            $this->redirect("/admin/products/variants?id={$productId}");
        }
    }

    private function handleImageUpload(array $file, int $productId): string
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/variants/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'variant_' . $productId . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Chyba při nahrávání obrázku");
        }
        
        return $fileName;
    }

    private function deleteImage(string $fileName): void
    {
        $filePath = __DIR__ . '/../../../public/uploads/variants/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function generateSuggestedCodes(array $usedCodes): array
    {
        $suggestedCodes = [];
        $alphabet = range('A', 'Z');
        foreach ($alphabet as $letter) {
            if (!in_array($letter, $usedCodes)) {
                $suggestedCodes[] = $letter;
                if (count($suggestedCodes) >= 5) break;
            }
        }
        return $suggestedCodes;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new Exception("View not found: {$view}");
        }
    }

    private function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    private function setSuccessMessage(string $message): void
    {
        $_SESSION['success_message'] = $message;
    }

    private function setErrorMessage(string $message): void
    {
        $_SESSION['error_message'] = $message;
    }
}