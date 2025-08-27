<?php

namespace App\Services;

use App\Interfaces\ProductServiceInterface;
use App\Services\DatabaseService;
use PDO;

class ProductService implements ProductServiceInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getRandomProducts(int $limit = 4): array
    {
        $sql = "SELECT * FROM items WHERE visible = 1 AND stock = 'Skladem' ORDER BY RAND() LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchProducts(string $query, int $limit = 50): array
    {
        $sql = 'SELECT * FROM items WHERE name LIKE :query AND visible = 1 LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProductWithDescription(string $id): array
    {
        $sql = "SELECT items.*, items_description.* FROM items 
               INNER JOIN items_description ON items.id = items_description.id_item
               WHERE visible = 1 AND items.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Chyba při načítání produktu: " . $e->getMessage());
            return null;
        }
    }

    public function formatCategory(string $category): string
    {
        return $category;
    }

    public function getProductsByCategory(string $category, int $limit = 50): array
    {
        $sql = "SELECT * FROM items WHERE visible = 1 AND category = :category LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function renderProductCard(array $item): string
    {
        $image = htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
        $price = htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8');
        $id = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
        
        return '<div class="product">
            <img src="' . $image . '" alt="' . $name . '">
            <a href="product.php?id=' . $id . '"><h3>' . $name . '</h3></a>
            <p class="price">' . $price . 'Kč</p>
            <form method="post" action="">
                <input type="hidden" name="item_id" value="' . $id . '">
                <button type="submit" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10.5 3.5a2.5 2.5 0 0 0-5 0V4h5zm1 0V4H15v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4h3.5v-.5a3.5 3.5 0 1 1 7 0M14 14V5H2v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1M8 7.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                    </svg>
                    <p class="add-to-cart">Přidat do košíku</p>
                </button>
            </form>
        </div>';
    }

    public function processProductInsert(array $postData, ?array $imageFile = null): int
    {
        try {
            $image = '';
            if ($imageFile && !empty($imageFile['name'])) {
                $image = $this->handleFileUpload('image');
                if ($image === false) {
                    throw new \Exception("Chyba při nahrávání obrázku");
                }
            }
            
            $stmt = $this->db->prepare("INSERT INTO items (name, product_code, description_main, price, price_without_dph, image, image_folder, mass, visible, category, stock) 
                VALUES (:name, :product_code, :description_main, :price, :price_without_dph, :image, :image_folder, :mass, :visible, :category, :stock)");
            
            $stmt->execute([
                ':name' => $postData['name'],
                ':product_code' => $postData['product_code'],
                ':description_main' => $postData['description_main'],
                ':price' => $postData['price'],
                ':price_without_dph' => $postData['price_without_dph'],
                ':image' => $image,
                ':image_folder' => $postData['image_folder'],
                ':mass' => $postData['mass'],
                ':visible' => $postData['visible'],
                ':category' => $postData['category'],
                ':stock' => $postData['stock_status'],
            ]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("Chyba při ukládání produktu: " . $e->getMessage());
        }
    }

    private function handleFileUpload(string $fileKey, string $uploadDir = 'uploads/'): string|false
    {
        if (empty($_FILES[$fileKey]['name'])) {
            return '';
        }
        
        $filename = basename($_FILES[$fileKey]['name']);
        $targetPath = '../' . $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
            return $uploadDir . $filename;
        }
        
        return false;
    }

    public function getProducts(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM items WHERE visible = 1";
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['name'])) {
            $sql .= " AND name LIKE :name";
            $params[':name'] = "%{$filters['name']}%";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createProduct(array $productData): int
    {
        $stmt = $this->db->prepare("INSERT INTO items (name, product_code, description_main, price, price_without_dph, image, image_folder, mass, visible, category, stock) 
            VALUES (:name, :product_code, :description_main, :price, :price_without_dph, :image, :image_folder, :mass, :visible, :category, :stock)");
        
        $stmt->execute([
            ':name' => $productData['name'],
            ':product_code' => $productData['product_code'] ?? '',
            ':description_main' => $productData['description_main'] ?? '',
            ':price' => $productData['price'],
            ':price_without_dph' => $productData['price_without_dph'] ?? 0,
            ':image' => $productData['image'] ?? '',
            ':image_folder' => $productData['image_folder'] ?? '',
            ':mass' => $productData['mass'] ?? 0,
            ':visible' => $productData['visible'] ?? 1,
            ':category' => $productData['category'] ?? '',
            ':stock' => $productData['stock'] ?? 'Skladem',
        ]);
        
        return $this->db->lastInsertId();
    }

    public function updateProduct(int $id, array $productData): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($productData as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE items SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function deleteProduct(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getFeaturedProducts(int $limit = 10): array
    {
        $sql = "SELECT * FROM items WHERE visible = 1 AND featured = 1 LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateInventoryAfterPaidOrder(int $orderNumber): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT id_product, count FROM orders_items WHERE order_number = :order_number");
            $stmt->execute([':order_number' => $orderNumber]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orderItems as $item) {
                $productId = $item['id_product'];
                $quantity = $item['count'];
                
                $updateStmt = $this->db->prepare("UPDATE items SET amount = GREATEST(amount - :quantity, 0) WHERE id = :product_id");
                $updateStmt->execute([
                    ':quantity' => $quantity,
                    ':product_id' => $productId
                ]);
                
                $checkStmt = $this->db->prepare("SELECT amount FROM items WHERE id = :product_id");
                $checkStmt->execute([':product_id' => $productId]);
                $currentAmount = $checkStmt->fetchColumn();
                
                if ($currentAmount <= 0) {
                    $stockStmt = $this->db->prepare("UPDATE items SET stock = 'Předobjednat' WHERE id = :product_id");
                    $stockStmt->execute([':product_id' => $productId]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Chyba při aktualizaci skladových zásob: " . $e->getMessage());
            return false;
        }
    }
}