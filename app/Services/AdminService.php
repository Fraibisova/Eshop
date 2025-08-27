<?php

namespace App\Services;

use PDO;
use Exception;

class AdminService
{
    private ?PDO $db = null;

    public function __construct()
    {
    }
    
    private function getDb(): PDO
    {
        if ($this->db === null) {
            $this->db = DatabaseService::getInstance()->getConnection();
        }
        return $this->db;
    }

    public function checkAdminRole(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 10) {
            header('location: index.php');
            exit();
        }
    }

    public function calculatePagination(int $totalItems, int $itemsPerPage, int $currentPage = 1): array
    {
        $currentPage = max($currentPage, 1);
        $offset = ($currentPage - 1) * $itemsPerPage;
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        return [
            'offset' => $offset,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage
        ];
    }

    public function renderPagination(int $totalPages, int $currentPage, string $baseUrl = '?', array $getParams = []): string
    {
        if ($totalPages <= 1) return '';
        
        $html = '<div class="pagination">';
        
        for ($i = 1; $i <= $totalPages; $i++) {
            $params = array_merge($getParams, ['page' => $i]);
            $url = $baseUrl . http_build_query($params);
            $activeClass = ($i === $currentPage) ? 'active' : '';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="' . $activeClass . '">' . $i . '</a>';
        }
        
        $html .= '</div>';
        return $html;
    }

    public function renderHeader(): void
    {
        echo '<nav>
            <a href="/admin/dashboard">Domů</a>
            <a href="/admin/newsletter">Newsletter</a>
            <a href="/admin/website">Editovat web</a>
            <a href="/admin/pages">Editovat stránky</a>
            <a href="/admin/products/add">Přidat zboží</a>
            <a href="/admin/products">Upravit zboží</a>
            <a href="/admin/upload">Nahrát fotky</a>
            <a href="/admin/orders">Objednávky</a>
            <a href="/">Přejít na web</a>
            <a href="/auth/logout">Odhlásit se</a>
        </nav>';
    }

    public function getDatabase(): PDO
    {
        return $this->getDb();
    }

    public function buildDynamicQuery(string $table, array $columns, array $getParams, string $orderBy = 'id DESC'): array
    {
        $filters = [];
        $params = [];
        
        foreach ($columns as $column) {
            if (!empty($getParams[$column])) {
                $filters[] = "$column LIKE :$column";
                $params[$column] = '%' . $getParams[$column] . '%';
            }
        }
        
        $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
        $query = "SELECT * FROM $table $whereClause ORDER BY $orderBy";
        $countQuery = "SELECT COUNT(*) FROM $table $whereClause";
        
        return [
            'query' => $query,
            'countQuery' => $countQuery,
            'params' => $params
        ];
    }

    public function executeCountQuery(string $countQuery, array $params): int
    {
        try {
            $stmt = $this->getDb()->prepare($countQuery);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new Exception("Chyba při počítání záznamů: " . $e->getMessage());
        }
    }

    public function executePaginatedQuery(string $query, array $params, int $offset, int $limit): array
    {
        try {
            $stmt = $this->getDb()->prepare($query . ' LIMIT ? OFFSET ?');
            
            $allParams = array_merge($params, [$limit, $offset]);
            $stmt->execute($allParams);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání dat: " . $e->getMessage());
        }
    }

    public function processProductUpdate(array $postData, string $currentImage = '', ?array $imageFile = null): bool
    {
        try {
            $image = $currentImage;
            if ($imageFile && !empty($imageFile['name'])) {
                $uploadResult = $this->handleFileUpload('image');
                if ($uploadResult !== false) {
                    $image = $uploadResult;
                }
            }
            
            $stock = isset($postData['stock_status']) ? $postData['stock_status'] : 
                     (isset($postData['stock']) ? $postData['stock'] : 'Není skladem');
            
            $sale = 0;
            if (isset($postData['sale'])) {
                $sale = (int)$postData['sale'];
                if ($sale < 0) $sale = 0;
                if ($sale > 100) $sale = 100;
            }
            
            $season = isset($postData['season']) && !empty($postData['season']) ? 
                      $postData['season'] : null;
            
            $stmt = $this->getDb()->prepare("
                UPDATE items 
                SET name = :name, 
                    product_code = :product_code, 
                    description_main = :description_main, 
                    price = :price, 
                    price_without_dph = :price_without_dph, 
                    sale = :sale,
                    season = :season,
                    image = :image, 
                    image_folder = :image_folder, 
                    mass = :mass, 
                    visible = :visible, 
                    category = :category, 
                    stock = :stock 
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $postData['id'],
                ':name' => $postData['name'],
                ':product_code' => $postData['product_code'],
                ':description_main' => $postData['description_main'],
                ':price' => $postData['price'],
                ':price_without_dph' => $postData['price_without_dph'],
                ':sale' => $sale,
                ':season' => $season,
                ':image' => $image,
                ':image_folder' => $postData['image_folder'],
                ':mass' => $postData['mass'],
                ':visible' => $postData['visible'],
                ':category' => $postData['category'],
                ':stock' => $stock,
            ]);
            
            return true;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při aktualizaci produktu: " . $e->getMessage());
        }
    }

    public function deleteProduct(int $id): bool
    {
        try {
            $this->getDb()->beginTransaction();
            
            $stmtDescription = $this->getDb()->prepare("DELETE FROM items_description WHERE id_item = :id");
            $stmtDescription->execute([':id' => $id]);
            
            $stmtItems = $this->getDb()->prepare("DELETE FROM items WHERE id = :id");
            $stmtItems->execute([':id' => $id]);
            
            $this->getDb()->commit();
            return true;
        } catch (Exception $e) {
            $this->getDb()->rollback();
            throw new Exception("Chyba při mazání produktu: " . $e->getMessage());
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

    public function renderStatusFilter(string $currentStatus = ''): string
    {
        $statuses = ['draft', 'scheduled', 'sent', 'sending'];
        
        $html = '<div class="status-filter">';
        $html .= '<a href="?" class="filter-link' . (empty($currentStatus) ? ' active' : '') . '">Všechny</a>';
        
        foreach ($statuses as $status) {
            $isActive = ($currentStatus === $status) ? ' active' : '';
            $html .= '<a href="?status=' . urlencode($status) . '" class="filter-link' . $isActive . '">' . ucfirst($status) . '</a>';
        }
        
        $html .= '</div>';
        return $html;
    }

    public function renderFilterForm(array $columns, array $getParams = []): string
    {
        $html = '<form method="GET">';
        
        foreach ($columns as $column) {
            $value = isset($getParams[$column]) ? htmlspecialchars($getParams[$column]) : '';
            $html .= '<label for="' . $column . '">' . ucfirst($column) . ':</label>';
            $html .= '<input type="text" name="' . $column . '" value="' . $value . '" id="' . $column . '">';
        }
        
        $html .= '<button type="submit">Filtrovat</button>';
        $html .= '</form>';
        
        return $html;
    }

    public function processProductInsert(array $postData, ?array $imageFile = null): int|false
    {
        try {
            $image = '';
            if ($imageFile && !empty($imageFile['name'])) {
                $image = $this->handleFileUpload('image');
                if ($image === false) {
                    throw new Exception("Chyba při nahrávání obrázku");
                }
            }
            
            $stmt = $this->getDb()->prepare("INSERT INTO items (name, product_code, description_main, price, price_without_dph, image, image_folder, mass, visible, category, stock) 
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
                ':stock' => $postData['stock'],
            ]);
            
            return $this->getDb()->lastInsertId();
        } catch (\PDOException $e) {
            throw new Exception("Chyba při ukládání produktu: " . $e->getMessage());
        }
    }

    public function getCancellationEmailTemplate(string $order_number, string $total_amount = "0"): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px;'>
                <h2 style='color: #d9534f;'>Zrušení objednávky #{$order_number}</h2>
                <p>Vaše objednávka v hodnotě {$total_amount} Kč byla úspěšně zrušena.</p>
                <p>Pokud máte jakékoliv dotazy, kontaktujte nás na fraibisovab@gmail.com</p>
                <hr style='border: 1px solid #ddd;'>
                <p style='font-size: 12px; color: #666;'>Touch the Magic &copy; " . date('Y') . "</p>
            </div>
        </body>
        </html>";
    }

    public function sendSimpleEmail(string $to, string $subject, string $html_content): bool
    {
        return mail($to, $subject, $html_content, [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'From' => 'fraibisovab@gmail.com'
        ]);
    }

    public function logMessage(string $message): void
    {
        error_log($message);
    }
}