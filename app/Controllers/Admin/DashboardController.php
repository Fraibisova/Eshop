<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;

class DashboardController
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
        $orders = $this->adminService->executePaginatedQuery(
            "SELECT id, order_number, name, surname, email, phone, price, currency, timestamp 
             FROM orders_user 
             ORDER BY timestamp DESC", 
            [], 
            0, 
            5
        );

        $this->render('admin/dashboard/index', [
            'recentOrders' => $orders,
            'adminService' => $this->adminService
        ]);
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/dashboard/index':
                include __DIR__ . '/../../Views/admin/dashboard/index.php';
                break;
        }
    }
}