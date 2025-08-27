<?php

namespace App\Controllers;

use App\Services\AuthorizationService;
use App\Services\PaginationService;
use App\Services\QueryBuilderService;
use App\Services\AdminUIService;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\UserServiceInterface;
use Exception;

class AdminController extends BaseController
{
    private AuthorizationService $authorizationService;
    private PaginationService $paginationService;
    private QueryBuilderService $queryBuilderService;
    private AdminUIService $adminUIService;
    private ProductServiceInterface $productService;
    private UserServiceInterface $userService;

    public function __construct() {
        parent::__construct();
        $this->authorizationService = new AuthorizationService();
        $this->paginationService = new PaginationService();
        $this->queryBuilderService = new QueryBuilderService();
        $this->adminUIService = new AdminUIService();
        $this->productService = new \App\Services\ProductService();
        $this->userService = new \App\Services\UserService();
        
        $this->checkAdminAccess();
    }

    private function checkAdminAccess(): void
    {
        $this->authorizationService->checkAdminRole();
    }

    public function addProduct(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        try {
            $requiredFields = ['name', 'product_code', 'price', 'category'];
            $errors = $this->validateRequired($requiredFields, $_POST);
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            $id_item = $this->productService->processProductInsert($_POST, $_FILES['image'] ?? null);
            
            if ($id_item) {
                return ['success' => true, 'message' => 'Produkt byl úspěšně přidán', 'product_id' => $id_item];
            } else {
                return ['success' => false, 'message' => 'Chyba při přidávání produktu'];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function editProduct(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        try {
            $requiredFields = ['id', 'name', 'product_code', 'price'];
            $errors = $this->validateRequired($requiredFields, $_POST);
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            $success = $this->productService->processProductUpdate($_POST, $_POST['current_image'] ?? '', $_FILES['image'] ?? null);
            
            if ($success) {
                return ['success' => true, 'message' => 'Produkt byl úspěšně aktualizován'];
            } else {
                return ['success' => false, 'message' => 'Chyba při aktualizaci produktu'];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteProduct(): array
    {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            return ['success' => false, 'message' => 'Neplatné ID produktu'];
        }

        try {
            $success = $this->productService->deleteProduct((int)$_GET['id']);
            
            if ($success) {
                return ['success' => true, 'message' => 'Produkt byl úspěšně smazán'];
            } else {
                return ['success' => false, 'message' => 'Chyba při mazání produktu'];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getProducts(array $filters = [], int $page = 1, int $limit = 10): array
    {
        try {
            $columns = ['name', 'product_code', 'category', 'price', 'visible'];
            $queryData = $this->queryBuilderService->buildDynamicQuery('items', $columns, $filters, 'id DESC');
            
            $totalItems = $this->queryBuilderService->executeCountQuery($queryData['countQuery'], $queryData['params']);
            $pagination = $this->paginationService->calculatePagination($totalItems, $limit, $page);
            
            $products = $this->queryBuilderService->executePaginatedQuery(
                $queryData['query'], 
                $queryData['params'], 
                $pagination['offset'], 
                $limit
            );

            return [
                'success' => true,
                'products' => $products,
                'pagination' => $pagination,
                'total' => $totalItems
            ];

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function dashboard(): void
    {
        try {
            $orders = $this->queryBuilderService->executePaginatedQuery("SELECT id, order_number, name, surname, email, phone, price, currency, timestamp FROM orders_user ORDER BY timestamp DESC", [], 0, 5);
            $this->renderDashboard($orders);
        } catch (\PDOException $e) {
            $this->handleError($e);
            echo "<p>Chyba při načítání objednávek: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    private function renderDashboard($orders): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="cs">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Panel</title>
            <link rel="stylesheet" href="/css/admin_style.css">
        </head>
        <body>
        <?php
        $this->adminUIService->renderHeader();
        ?>

        <h2>Posledních 5 objednávek:</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Číslo objednávky</th>
                    <th>Jméno</th>
                    <th>Příjmení</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Cena</th>
                    <th>Měna</th>
                    <th>Čas objednávky</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                        <td><?= htmlspecialchars($order['name']) ?></td>
                        <td><?= htmlspecialchars($order['surname']) ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= htmlspecialchars($order['price']) ?></td>
                        <td><?= htmlspecialchars($order['currency']) ?></td>
                        <td><?= htmlspecialchars($order['timestamp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        </body>
        </html>
        <?php
    }

    public function newsletter(): void
    {
        try {
            $controller = new \App\Controllers\Admin\NewsletterController();
            $controller->index();
        } catch (Exception $e) {
            $this->handleError($e);
            echo "<p>Chyba při načítání newsletteru: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    public function renderHeader(): string
    {
        return '<nav>
            <a href="/admin/dashboard">Domů</a>
            <a href="/admin/newsletter">Newsletter</a>
            <a href="/admin/edit_website">Editovat web</a>
            <a href="/admin/edit_pages">Editovat stránky</a>
            <a href="/admin/add_goods">Přidat zboží</a>
            <a href="/admin/edit_goods">Upravit zboží</a>
            <a href="/admin/upload_img">Nahrát fotky</a>
            <a href="/admin/orders">Objednávky</a>
            <a href="/">Přejít na web</a>
            <a href="/auth/logout">Odhlásit se</a>
        </nav>';
    }
}