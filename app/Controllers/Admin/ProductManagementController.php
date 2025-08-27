<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use App\Services\ProductService;
use App\Services\TemplateService;
use Exception;
use PDO;

class ProductManagementController
{
    private AdminService $adminService;
    private ProductService $productService;
    private TemplateService $templateService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->adminService = new AdminService();
        $this->productService = new ProductService();
        $this->templateService = new TemplateService();
    }

    public function addProduct(): void
    {
        $this->adminService->checkAdminRole();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] == 10) {
            try {
                $id_item = $this->productService->processProductInsert($_POST, $_FILES['image'] ?? null);
                $this->processProductDescription($id_item, $_POST);
                $_SESSION['success_message'] = "Produkt byl úspěšně přidán!";
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $this->renderAddProductForm();
    }

    public function editProduct(): void
    {
        $this->adminService->checkAdminRole();
        
        $productId = $_GET['id'] ?? null;
        if (!$productId) {
            header("Location: /admin/products");
            exit();
        }

        $db = $this->adminService->getDatabase();
        $message = '';

        try {
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $_SESSION['message'] = "Produkt nenalezen.";
                header("Location: /admin/products");
                exit();
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $success = $this->updateProduct($productId, $_POST, $_FILES['image'] ?? null);
                    if ($success) {
                        $message = "Produkt byl úspěšně upraven!";
                        
                        $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
                        $stmt->execute([$productId]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $message = "Chyba při ukládání produktu.";
                    }
                } catch (Exception $e) {
                    $message = "Chyba při ukládání: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $message = "Chyba při načítání produktu: " . $e->getMessage();
            $product = [];
        }

        $this->render('admin/products/edit', [
            'item' => $product,
            'message' => $message,
            'adminService' => $this->adminService
        ]);
    }

    public function listProducts(): void
    {
        $this->adminService->checkAdminRole();

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $searchTerm = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';

        $products = $this->getFilteredProducts($searchTerm, $category, $offset, $limit);
        $totalProducts = $this->getTotalProductCount($searchTerm, $category);
        $totalPages = ceil($totalProducts / $limit);

        $this->renderProductList($products, $page, $totalPages, $searchTerm, $category);
    }

    public function deleteProduct(): void
    {
        $this->adminService->checkAdminRole();
        
        $productId = $_GET['id'] ?? null;
        if (!$productId) {
            $_SESSION['error'] = 'ID produktu není specifikováno.';
            header("Location: /admin/products");
            exit();
        }

        $db = $this->adminService->getDatabase();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            try {
                $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $_SESSION['error'] = 'Produkt nenalezen.';
                    header("Location: /admin/products");
                    exit();
                }
                
                $this->render('admin/products/delete', [
                    'product' => $product,
                    'adminService' => $this->adminService
                ]);
                return;
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Chyba při načítání produktu: ' . $e->getMessage();
                header("Location: /admin/products");
                exit();
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] == 10) {
            try {
                $stmt = $db->prepare("SELECT name FROM items WHERE id = ?");
                $stmt->execute([$productId]);
                $productName = $stmt->fetchColumn();
                
                if (!$productName) {
                    throw new Exception("Produkt s ID {$productId} neexistuje.");
                }
                
                $deletedVariants = 0;
                try {
                    $stmt = $db->prepare("DELETE FROM items WHERE parent_item_id = ?");
                    $variantDeleteResult = $stmt->execute([$productId]);
                    $deletedVariants = $stmt->rowCount();
                } catch (Exception $e1) {
                    try {
                        $stmt = $db->prepare("DELETE FROM product_variants WHERE product_id = ?");
                        $variantDeleteResult = $stmt->execute([$productId]);
                        $deletedVariants = $stmt->rowCount();
                    } catch (Exception $e2) {
                        $deletedVariants = 0;
                    }
                }
                
                $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
                $productDeleteResult = $stmt->execute([$productId]);
                $deletedProducts = $stmt->rowCount();
                
                if ($deletedProducts > 0) {
                    $_SESSION['success'] = "Produkt '{$productName}' byl úspěšně smazán" . 
                        ($deletedVariants > 0 ? " společně s {$deletedVariants} variantami." : ".");
                } else {
                    throw new Exception("Produkt se nepodařilo smazat - možná již neexistuje.");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Chyba při mazání produktu: ' . $e->getMessage();
            }
            
            header("Location: /admin/products");
            exit();
        }
    }

    public function editDescription(): void
    {
        $this->adminService->checkAdminRole();
        
        $productId = $_GET['id'] ?? null;
        if (!$productId) {
            $_SESSION['error'] = 'ID produktu není specifikováno.';
            header("Location: /admin/products");
            exit();
        }

        $db = $this->adminService->getDatabase();
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] == 10) {
            try {
                $stmt = $db->prepare("
                    UPDATE items 
                    SET paragraph1 = ?, paragraph2 = ?, paragraph3 = ?, 
                        paragraph4 = ?, paragraph5 = ?, paragraph6 = ?
                    WHERE id = ?
                ");
                
                $success = $stmt->execute([
                    $_POST['paragraph1'] ?? '',
                    $_POST['paragraph2'] ?? '',
                    $_POST['paragraph3'] ?? '',
                    $_POST['paragraph4'] ?? '',
                    $_POST['paragraph5'] ?? '',
                    $_POST['paragraph6'] ?? '',
                    $productId
                ]);
                
                if ($success) {
                    $message = 'Popis produktu byl úspěšně aktualizován.';
                } else {
                    throw new Exception('Chyba při ukládání změn.');
                }
                
            } catch (Exception $e) {
                $message = 'Chyba: ' . $e->getMessage();
            }
        }
        
        try {
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $_SESSION['error'] = 'Produkt nenalezen.';
                header("Location: /admin/products");
                exit();
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Chyba při načítání produktu: ' . $e->getMessage();
            header("Location: /admin/products");
            exit();
        }
        
        $this->render('admin/products/description/edit', [
            'item' => $product,
            'message' => $message,
            'adminService' => $this->adminService
        ]);
    }

    private function renderAddProductForm(): void
    {
        $message = '';
        if (isset($_SESSION['success_message'])) {
            $message = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        } elseif (isset($_SESSION['error_message'])) {
            $message = 'Chyba: ' . $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        
        $this->render('admin/products/add', [
            'adminService' => $this->adminService,
            'message' => $message
        ]);
    }
    
    private function renderAddProductFormLegacy(): void
    {
        $this->adminService->renderHeader();
        
        echo '<div class="container">
                <h1>Přidat nový produkt</h1>';

        if (isset($_SESSION['success_message'])) {
            echo '<p class="success">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['error_message'])) {
            echo '<p class="error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
            unset($_SESSION['error_message']);
        }

        echo '<form action="" method="post" enctype="multipart/form-data" class="product-form">
                <div class="form-group">
                    <label for="name">Název produktu:</label>
                    <input type="text" id="name" name="name" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="product_code">Kód produktu:</label>
                    <input type="text" id="product_code" name="product_code" required>
                </div>

                <div class="form-group">
                    <label for="description_main">Popis produktu:</label>
                    <textarea id="description_main" name="description_main" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Cena (včetně DPH):</label>
                    <input type="number" step="0.01" id="price" name="price" required>
                </div>

                <div class="form-group">
                    <label for="price_without_dph">Cena (bez DPH):</label>
                    <input type="number" step="0.01" id="price_without_dph" name="price_without_dph" required>
                </div>

                <div class="form-group">
                    <label for="image">Obrázek:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="image_folder">Složka obrázků:</label>
                    <input type="text" id="image_folder" name="image_folder" required>
                </div>

                <div class="form-group">
                    <label for="mass">Hmotnost (g):</label>
                    <input type="number" id="mass" name="mass">
                </div>

                <div class="form-group">
                    <label for="category">Kategorie:</label>
                    <input type="text" id="category" name="category" required>
                </div>

                <div class="form-group">
                    <label for="stock_status">Stav skladu:</label>
                    <select id="stock_status" name="stock_status" required>
                        <option value="Skladem">Skladem</option>
                        <option value="Není skladem">Není skladem</option>
                        <option value="Předobjednat">Předobjednat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="visible">Viditelný:</label>
                    <select id="visible" name="visible">
                        <option value="1">Ano</option>
                        <option value="0">Ne</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Detailní popis:</label>
                    <textarea id="description" name="description" rows="10"></textarea>
                </div>

                <div class="form-group">
                    <label for="additional_info">Dodatečné informace:</label>
                    <textarea id="additional_info" name="additional_info" rows="5"></textarea>
                </div>

                <button type="submit" class="btn-submit">Přidat produkt</button>
              </form>
              </div>';
    }

    private function renderEditProductForm(array $product): void
    {
        $this->adminService->renderHeader();
        
        echo '<div class="container">
                <h1>Upravit produkt</h1>';

        if (isset($_SESSION['success_message'])) {
            echo '<p class="success">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['error_message'])) {
            echo '<p class="error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
            unset($_SESSION['error_message']);
        }

        echo '<form action="" method="post" enctype="multipart/form-data" class="product-form">
                <div class="form-group">
                    <label for="name">Název produktu:</label>
                    <input type="text" id="name" name="name" value="' . htmlspecialchars($product['name']) . '" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="product_code">Kód produktu:</label>
                    <input type="text" id="product_code" name="product_code" value="' . htmlspecialchars($product['product_code'] ?? '') . '" required>
                </div>

                <div class="form-group">
                    <label for="price">Cena (včetně DPH):</label>
                    <input type="number" step="0.01" id="price" name="price" value="' . htmlspecialchars($product['price']) . '" required>
                </div>

                <div class="form-group">
                    <label for="category">Kategorie:</label>
                    <input type="text" id="category" name="category" value="' . htmlspecialchars($product['category']) . '" required>
                </div>

                <div class="form-group">
                    <label for="stock_status">Stav skladu:</label>
                    <select id="stock_status" name="stock_status" required>
                        <option value="Skladem"' . ($product['stock'] == 'Skladem' ? ' selected' : '') . '>Skladem</option>
                        <option value="Není skladem"' . ($product['stock'] == 'Není skladem' ? ' selected' : '') . '>Není skladem</option>
                        <option value="Předobjednat"' . ($product['stock'] == 'Předobjednat' ? ' selected' : '') . '>Předobjednat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="visible">Viditelný:</label>
                    <select id="visible" name="visible">
                        <option value="1"' . ($product['visible'] == 1 ? ' selected' : '') . '>Ano</option>
                        <option value="0"' . ($product['visible'] == 0 ? ' selected' : '') . '>Ne</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Uložit změny</button>
              </form>
              </div>';
    }

    private function renderProductList(array $products, int $currentPage, int $totalPages, string $searchTerm, string $category): void
    {
        $this->adminService->renderHeader();
        
        echo '<div class="container">
                <h1>Správa produktů</h1>

                <div class="admin-actions">
                    <a href="add_product.php" class="btn btn-primary">Přidat nový produkt</a>
                </div>

                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Hledat produkty..." value="' . htmlspecialchars($searchTerm) . '">
                    <input type="text" name="category" placeholder="Kategorie..." value="' . htmlspecialchars($category) . '">
                    <button type="submit">Hledat</button>
                </form>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Název</th>
                            <th>Kategorie</th>
                            <th>Cena</th>
                            <th>Sklad</th>
                            <th>Viditelný</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($products as $product) {
            echo '<tr>
                    <td>' . htmlspecialchars($product['id']) . '</td>
                    <td>' . htmlspecialchars($product['name']) . '</td>
                    <td>' . htmlspecialchars($product['category']) . '</td>
                    <td>' . number_format($product['price'], 0, ',', ' ') . ' Kč</td>
                    <td>' . htmlspecialchars($product['stock']) . '</td>
                    <td>' . ($product['visible'] ? 'Ano' : 'Ne') . '</td>
                    <td>
                        <a href="edit_product.php?id=' . $product['id'] . '" class="btn btn-small">Upravit</a>
                        <form method="POST" action="delete_product.php" style="display: inline;" onsubmit="return confirm(\'Opravdu smazat?\')">
                            <input type="hidden" name="product_id" value="' . $product['id'] . '">
                            <button type="submit" class="btn btn-small btn-danger">Smazat</button>
                        </form>
                    </td>
                  </tr>';
        }

        echo '    </tbody>
                </table>';

        if ($totalPages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                $class = $i == $currentPage ? 'active' : '';
                $url = "?page=$i" . ($searchTerm ? "&search=" . urlencode($searchTerm) : "") . ($category ? "&category=" . urlencode($category) : "");
                echo "<a href='$url' class='$class'>$i</a>";
            }
            echo '</div>';
        }

        echo '</div>';
    }

    private function processProductDescription(int $idItem, array $postData): void
    {
        $db = $this->adminService->getDatabase();
        
        $stmt = $db->prepare("
            INSERT INTO items_description (id_item, description, additional_info) 
            VALUES (:id_item, :description, :additional_info)
            ON DUPLICATE KEY UPDATE 
            description = VALUES(description),
            additional_info = VALUES(additional_info)
        ");
        
        $stmt->execute([
            'id_item' => $idItem,
            'description' => $postData['description'] ?? '',
            'additional_info' => $postData['additional_info'] ?? ''
        ]);
    }

    private function updateProduct(int $productId, array $postData, ?array $imageFile): bool
    {
        try {
            $db = $this->adminService->getDatabase();
            
            $imageUpdate = '';
            $imageParams = [];
            if ($imageFile && !empty($imageFile['name'])) {
                $image = $this->handleFileUpload($imageFile);
                if ($image !== false) {
                    $imageUpdate = ', image = :image';
                    $imageParams[':image'] = $image;
                }
            }
            
            $sql = "UPDATE items SET 
                        name = :name, 
                        product_code = :product_code, 
                        price = :price, 
                        category = :category, 
                        stock = :stock, 
                        visible = :visible" . $imageUpdate . "
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            
            $params = [
                ':name' => $postData['name'],
                ':product_code' => $postData['product_code'] ?? '',
                ':price' => $postData['price'],
                ':category' => $postData['category'],
                ':stock' => $postData['stock_status'],
                ':visible' => $postData['visible'] ?? 1,
                ':id' => $productId
            ];
            
            $params = array_merge($params, $imageParams);
            
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Error updating product: " . $e->getMessage());
            throw new Exception("Chyba při aktualizaci produktu: " . $e->getMessage());
        }
    }
    
    private function handleFileUpload(array $file, string $uploadDir = 'public/uploads/'): string|false
    {
        if (empty($file['name'])) {
            return '';
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Nepodporovaný typ souboru. Povolené jsou pouze obrázky.');
        }
        
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxFileSize) {
            throw new Exception('Soubor je příliš velký. Maximální velikost je 5MB.');
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $targetPath;
        }
        
        return false;
    }

    private function getFilteredProducts(string $searchTerm, string $category, int $offset, int $limit): array
    {
        $db = $this->adminService->getDatabase();
        
        $sql = "SELECT * FROM items WHERE 1=1";
        $params = [];

        if ($searchTerm) {
            $sql .= " AND name LIKE :search";
            $params['search'] = "%$searchTerm%";
        }

        if ($category) {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        $sql .= " ORDER BY id DESC LIMIT :offset, :limit";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getVariantCount(int $itemId): int
    {
        try {
            $db = $this->adminService->getDatabase();
            $sql = "SELECT COUNT(*) as count FROM product_variants WHERE parent_item_id = :item_id AND is_active = TRUE";
            $stmt = $db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    private function getTotalProductCount(string $searchTerm, string $category): int
    {
        $db = $this->adminService->getDatabase();
        
        $sql = "SELECT COUNT(*) FROM items WHERE 1=1";
        $params = [];

        if ($searchTerm) {
            $sql .= " AND name LIKE :search";
            $params['search'] = "%$searchTerm%";
        }

        if ($category) {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }

    public function listProductsWithVariants(): void
    {
        $this->adminService->checkAdminRole();

        $itemsPerPage = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $itemsPerPage;

        $messages = [];
        
        try {
            $totalItems = $this->getTotalProductCount('', '');
            $totalPages = ceil($totalItems / $itemsPerPage);

            $db = $this->adminService->getDatabase();
            $sql = "SELECT * FROM items ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $itemsPerPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $messages[] = ['type' => 'error', 'text' => 'Chyba při načítání produktů: ' . $e->getMessage()];
            $items = [];
            $totalItems = 0;
            $totalPages = 0;
        }
        
        if (empty($items)) {
            $messages[] = ['type' => 'error', 'text' => 'Žádné produkty nebyly nalezeny v databázi. Celkem produktů: ' . $totalItems . '. SQL: ' . $sql . '. Offset: ' . $offset . ', Limit: ' . $itemsPerPage];
        } else {
            $messages[] = ['type' => 'success', 'text' => 'Nalezeno ' . count($items) . ' produktů z celkových ' . $totalItems];
        }

        foreach ($items as &$item) {
            $item['variant_count'] = $this->getVariantCount($item['id']);
        }
        if (isset($_SESSION['success'])) {
            $messages[] = ['type' => 'success', 'text' => $_SESSION['success']];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $messages[] = ['type' => 'error', 'text' => $_SESSION['error']];
            unset($_SESSION['error']);
        }

        $pagination = [
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];

        $variantCounts = [];
        foreach ($items as $item) {
            $variantCounts[$item['id']] = $item['variant_count'];
        }

        $this->render('admin/products/index', [
            'items' => $items,
            'variantCounts' => $variantCounts,
            'messages' => $messages,
            'pagination' => $pagination,
            'adminService' => $this->adminService
        ]);
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/products/index':
                include __DIR__ . '/../../Views/admin/products/index.php';
                break;
            case 'admin/products/listing':
                include __DIR__ . '/../../Views/admin/products/listing.php';
                break;
            case 'admin/products/edit':
                include __DIR__ . '/../../Views/admin/products/edit.php';
                break;
            case 'admin/products/add':
                include __DIR__ . '/../../Views/admin/products/add.php';
                break;
            case 'admin/products/delete':
                include __DIR__ . '/../../Views/admin/products/delete.php';
                break;
            case 'admin/products/description/edit':
                include __DIR__ . '/../../Views/admin/products/description/edit.php';
                break;
            default:
                throw new Exception("Template not found: {$template}");
        }
    }
}