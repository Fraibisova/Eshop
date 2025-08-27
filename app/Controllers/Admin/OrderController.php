<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use PDO;

class OrderController
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
        $recordsPerPage = 10;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $columns = [
            'id', 'order_number', 'name', 'surname', 'email', 'phone', 
            'street', 'house_number', 'city', 'zipcode', 'shipping_method', 
            'payment_method', 'branch', 'ico', 'dic', 'company_name', 
            'price', 'currency', 'payment_status', 'order_status'
        ];

        $queryData = $this->adminService->buildDynamicQuery('orders_user', $columns, $_GET, 'id DESC');
        $totalRecords = $this->adminService->executeCountQuery($queryData['countQuery'], $queryData['params']);
        $pagination = $this->adminService->calculatePagination($totalRecords, $recordsPerPage, $currentPage);
        $orders = $this->adminService->executePaginatedQuery($queryData['query'], $queryData['params'], $pagination['offset'], $recordsPerPage);

        $this->render('admin/orders/index', [
            'orders' => $orders,
            'columns' => $columns,
            'pagination' => $pagination,
            'getParams' => $_GET,
            'adminService' => $this->adminService
        ]);
    }

    public function detail(): void
    {
        if (!isset($_GET['order_number']) || !is_numeric($_GET['order_number'])) {
            displayErrorMessage("Neplatné číslo objednávky.");
            $this->redirect('/admin/orders');
            return;
        }

        $orderNumber = intval($_GET['order_number']);
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $this->handleOrderUpdate($orderNumber);
        }

        $orderItems = $this->getOrderItems($orderNumber);
        if (empty($orderItems)) {
            displayErrorMessage("Pro tuto objednávku nebyly nalezeny žádné položky.");
            $this->redirect('/admin/orders');
            return;
        }

        $orderInfo = $this->getOrderInfo($orderNumber);
        $orderSummary = $this->getOrderSummary($orderItems);

        $this->render('admin/orders/detail', [
            'orderNumber' => $orderNumber,
            'orderItems' => $orderItems,
            'orderInfo' => $orderInfo,
            'orderSummary' => $orderSummary,
            'message' => $message,
            'messageType' => $messageType,
            'adminService' => $this->adminService
        ]);
    }

    private function getOrderItems(int $orderNumber): array
    {
        $sql = "
            SELECT 
                oi.id AS order_item_id,
                oi.id_product,
                oi.variant_id,
                oi.order_number,
                oi.count,
                oi.is_preorder,
                i.name AS product_name,
                i.product_code,
                i.description_main,
                i.price AS base_price,
                i.price_without_dph,
                pv.variant_name,
                pv.variant_code,
                pv.price_modifier,
                pv.price_override,
                pv.stock_status,
                pv.color,
                pv.material,
                CASE 
                    WHEN oi.variant_id IS NOT NULL AND pv.price_override IS NOT NULL THEN pv.price_override
                    WHEN oi.variant_id IS NOT NULL AND pv.price_modifier IS NOT NULL THEN i.price + pv.price_modifier
                    ELSE i.price 
                END AS final_price
            FROM orders_items oi
            JOIN items i ON oi.id_product = i.id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            WHERE oi.order_number = :order_number
            ORDER BY oi.id
        ";

        $stmt = $this->adminService->getDatabase()->prepare($sql);
        $stmt->execute(['order_number' => $orderNumber]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getOrderInfo(int $orderNumber): array
    {
        $sql = "SELECT order_status FROM orders_user WHERE order_number = :order_number";
        $stmt = $this->adminService->getDatabase()->prepare($sql);
        $stmt->execute(['order_number' => $orderNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function getOrderSummary(array $orderItems): array
    {
        $totalItems = 0;
        $preorderItems = 0;
        $totalPrice = 0;
        
        foreach ($orderItems as $item) {
            $totalItems += $item['count'];
            $totalPrice += $item['final_price'] * $item['count'];
            
            if ($item['is_preorder']) {
                $preorderItems += $item['count'];
            }
        }
        
        return [
            'total_items' => $totalItems,
            'preorder_items' => $preorderItems,
            'total_price' => $totalPrice,
            'has_preorder' => $preorderItems > 0
        ];
    }

    private function handleOrderUpdate(int $orderNumber): string
    {
        $db = $this->adminService->getDatabase();
        
        if (isset($_POST['order_status']) && in_array($_POST['order_status'], ['waiting', 'cancel', 'completed', 'processed', 'send'])) {
            $newStatus = $_POST['order_status'];
            $trackingNumber = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
            
            $oldStatusStmt = $db->prepare("SELECT order_status FROM orders_user WHERE order_number = :order_number");
            $oldStatusStmt->execute(['order_number' => $orderNumber]);
            $oldStatus = $oldStatusStmt->fetchColumn();

            $updateParams = ['order_status' => $newStatus, 'order_number' => $orderNumber];
            $updateSql = "UPDATE orders_user SET order_status = :order_status";
            
            if (!empty($trackingNumber)) {
                $updateSql .= ", tracking_number = :tracking_number";
                $updateParams['tracking_number'] = $trackingNumber;
            }
            
            $updateSql .= " WHERE order_number = :order_number";
            $updateStmt = $db->prepare($updateSql);
            $result = $updateStmt->execute($updateParams);

            if ($result) {
                $message = "Stav objednávky byl úspěšně aktualizován na: " . htmlspecialchars($newStatus);
                
                if (!empty($trackingNumber)) {
                    $message .= "\nSledovací číslo bylo úspěšně uloženo: " . htmlspecialchars($trackingNumber);
                }
                
                return $message;
            } else {
                return "Chyba při aktualizaci stavu objednávky.";
            }
        }

        if (isset($_POST['update_tracking']) && isset($_POST['tracking_number_only'])) {
            $trackingNumber = trim($_POST['tracking_number_only']);
            
            if (!empty($trackingNumber)) {
                $updateStmt = $db->prepare("UPDATE orders_user SET tracking_number = :tracking_number WHERE order_number = :order_number");
                $result = $updateStmt->execute([
                    'tracking_number' => $trackingNumber,
                    'order_number' => $orderNumber
                ]);
                
                if ($result) {
                    return "Sledovací číslo bylo úspěšně aktualizováno: " . htmlspecialchars($trackingNumber);
                } else {
                    return "Chyba při aktualizaci sledovacího čísla.";
                }
            } else {
                return "Sledovací číslo nemůže být prázdné.";
            }
        }

        return '';
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/orders/index':
                include __DIR__ . '/../../Views/admin/orders/index.php';
                break;
            case 'admin/orders/detail':
                include __DIR__ . '/../../Views/admin/orders/detail.php';
                break;
        }
    }
}