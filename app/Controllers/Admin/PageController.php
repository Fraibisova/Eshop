<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use Exception;
use PDO;

class PageController
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

    public function list(): void
    {
        $db = $this->adminService->getDatabase();
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);
        
        try {
            $stmt = $db->prepare("SELECT * FROM pages ORDER BY id DESC");
            $stmt->execute();
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pages = [];
            $message = 'Chyba při načítání stránek: ' . $e->getMessage();
        }
        
        $messages = [];
        if (!empty($message)) {
            $messages[] = [
                'type' => strpos($message, 'Chyba') !== false ? 'error' : 'success',
                'text' => $message
            ];
        }
        
        $this->render('admin/pages/index', [
            'allPages' => $pages,  
            'adminService' => $this->adminService,
            'messages' => $messages 
        ]);
    }

    public function add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = $_POST['content'];

            if ($title && $slug && $content) {
                try {
                    global $pdo;
                    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (:title, :slug, :content)");
                    $stmt->execute(['title' => $title, 'slug' => $slug, 'content' => $content]);
                    $this->setSuccessMessage("Stránka byla vytvořena.");
                    $this->redirect('/admin/pages');
                } catch (Exception $e) {
                    $this->setErrorMessage($e->getMessage());
                }
            } else {
                $this->setErrorMessage("Vyplňte všechna pole.");
            }
        }

        $this->render('admin/pages/add');
    }

    public function edit(): void
    {
        $db = $this->adminService->getDatabase();
        $pageId = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? null;
        $message = '';

        if ($action === 'delete' && $pageId) {
            try {
                $stmt = $db->prepare("DELETE FROM pages WHERE id = :id");
                $stmt->execute(['id' => $pageId]);
                $_SESSION['message'] = "Stránka byla smazána.";
                header('Location: /admin/pages');
                exit;
            } catch (Exception $e) {
                $message = 'Chyba při mazání: ' . $e->getMessage();
            }
        }

        $page = ['title' => '', 'slug' => '', 'content' => ''];

        if ($pageId && $action !== 'delete') {
            try {
                $stmt = $db->prepare("SELECT * FROM pages WHERE id = :id");
                $stmt->execute(['id' => $pageId]);
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$page) {
                    $message = "Stránka nenalezena.";
                    $page = ['title' => '', 'slug' => '', 'content' => ''];
                }
            } catch (Exception $e) {
                $message = 'Chyba při načítání stránky: ' . $e->getMessage();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = $_POST['content'];

            if ($title && $slug && $content) {
                try {
                    if ($pageId) {
                        $stmt = $db->prepare("UPDATE pages SET title = :title, slug = :slug, content = :content WHERE id = :id");
                        $stmt->execute(['title' => $title, 'slug' => $slug, 'content' => $content, 'id' => $pageId]);
                        $message = "Stránka byla aktualizována.";
                    } else {
                        $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (:title, :slug, :content)");
                        $stmt->execute(['title' => $title, 'slug' => $slug, 'content' => $content]);
                        $message = "Stránka byla vytvořena.";
                    }
                    
                    if ($pageId) {
                        $stmt = $db->prepare("SELECT * FROM pages WHERE id = :id");
                        $stmt->execute(['id' => $pageId]);
                        $page = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $message = 'Chyba při ukládání: ' . $e->getMessage();
                }
            } else {
                $message = "Vyplňte všechna pole.";
            }
        }

        $allPages = [];
        try {
            $stmt = $db->prepare("SELECT * FROM pages ORDER BY id DESC");
            $stmt->execute();
            $allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $message = 'Chyba při načítání seznamu stránek: ' . $e->getMessage();
        }

        $this->render('admin/pages/edit', [
            'page' => $page, 
            'pageId' => $pageId,
            'allPages' => $allPages,
            'adminService' => $this->adminService,
            'message' => $message
        ]);
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/pages/index':
                include __DIR__ . '/../../Views/admin/pages/index.php';
                break;
            case 'admin/pages/edit':
                include __DIR__ . '/../../Views/admin/pages/edit.php';
                break;
            default:
                throw new Exception("Template not found: {$template}");
        }
    }
}