<?php

namespace App\Controllers\Modern;

use App\Controllers\BaseController;
use App\Services\AuthorizationService;
use App\Services\PaginationService;
use App\Services\QueryBuilderService;
use App\Services\AdminUIService;
use App\Interfaces\ProductServiceInterface;
use Exception;

class AdminController extends BaseController
{
    private AuthorizationService $authService;
    private PaginationService $paginationService;
    private QueryBuilderService $queryBuilder;
    private AdminUIService $uiService;
    private ProductServiceInterface $productService;

    public function __construct(
        AuthorizationService $authService,
        PaginationService $paginationService,
        QueryBuilderService $queryBuilder,
        AdminUIService $uiService,
        ProductServiceInterface $productService
    ) {
        parent::__construct();
        $this->authService = $authService;
        $this->paginationService = $paginationService;
        $this->queryBuilder = $queryBuilder;
        $this->uiService = $uiService;
        $this->productService = $productService;
        
        $this->checkAdminAccess();
    }

    private function checkAdminAccess(): void
    {
        $this->authService->checkAdminRole();
    }

    public function dashboard(): void
    {
        try {
            $page = $_GET['page'] ?? 1;
            $itemsPerPage = 10;
            
            $queryData = $this->queryBuilder->buildDynamicQuery(
                'orders_user', 
                ['name', 'surname', 'email'], 
                $_GET
            );
            
            $totalItems = $this->queryBuilder->executeCountQuery(
                $queryData['countQuery'], 
                $queryData['params']
            );
            
            $pagination = $this->paginationService->calculatePagination(
                $totalItems, 
                $itemsPerPage, 
                $page
            );
            
            $orders = $this->queryBuilder->executePaginatedQuery(
                $queryData['query'],
                $queryData['params'],
                $pagination['offset'],
                $itemsPerPage
            );
            
            $this->renderDashboard($orders, $pagination);
            
        } catch (Exception $e) {
            $this->handleError($e);
            echo "<p>Chyba při načítání objednávek: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    private function renderDashboard(array $orders, array $pagination): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="cs">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Panel - Dashboard</title>
            <link rel="stylesheet" href="/css/admin_style.css">
        </head>
        <body>
        <?php
        $this->uiService->renderHeader();
        
        echo $this->uiService->renderBreadcrumb([
            ['title' => 'Admin', 'url' => '/admin'],
            ['title' => 'Dashboard', 'url' => '']
        ]);
        
        echo $this->uiService->renderFilterForm(['name', 'surname', 'email'], $_GET);
        ?>

        <h2>Posledních objednávek (stránka <?php echo $pagination['currentPage']; ?>):</h2>
        <table class="admin-table">
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
                    <th>Datum</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                    <td><?php echo htmlspecialchars($order['surname']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><?php echo htmlspecialchars($order['phone']); ?></td>
                    <td><?php echo htmlspecialchars($order['price']); ?></td>
                    <td><?php echo htmlspecialchars($order['currency']); ?></td>
                    <td><?php echo htmlspecialchars($order['timestamp']); ?></td>
                    <td>
                        <a href="/admin/order/<?php echo $order['id']; ?>" class="btn btn-sm">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        echo $this->paginationService->renderPagination(
            $pagination['totalPages'],
            $pagination['currentPage'],
            '/admin/dashboard?',
            $_GET
        );
        ?>

        </body>
        </html>
        <?php
    }
}