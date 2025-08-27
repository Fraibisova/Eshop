<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use App\Services\ContentService;
use PDO;

class WebsiteController
{
    private AdminService $adminService;
    private ContentService $contentService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->adminService = new AdminService();
        $this->adminService->checkAdminRole();
        $this->contentService = new ContentService();
    }

    public function editWebsite(): void
    {
        $sections = ['slider', 'intro_text', 'categories', 'left_menu', 'before_footer', 'footer', 'mobile_nav'];
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                foreach ($sections as $section) {
                    $content = $_POST[$section] ?? '';
                    $this->contentService->updateHomepageContent($section, $content);
                }
                $message = "Změny byly uloženy!";
            } catch (\Exception $e) {
                $message = "Chyba: " . $e->getMessage();
            }
        }

        $this->render('admin/website/edit', [
            'sections' => $sections,
            'message' => $message,
            'adminService' => $this->adminService,
            'contentService' => $this->contentService
        ]);
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/website/edit':
                include __DIR__ . '/../../Views/admin/website/edit.php';
                break;
        }
    }
}