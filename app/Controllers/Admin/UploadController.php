<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;

class UploadController 
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
        $folder = $_GET['folder'] ?? '';
        $messages = [];
        $images = [];
        $folders = [];
        
        if (!empty($folder)) {
            $uploadDir = __DIR__ . '/../../../public/uploads/' . $folder;
            
            if (!is_dir($uploadDir)) {
                $messages[] = ['type' => 'error', 'text' => 'Složka neexistuje.'];
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
                    $messages = array_merge($messages, $this->handleUpload($uploadDir));
                }
                
                $images = $this->getImages($uploadDir, $folder);
            }
        } else {
            $folders = $this->getFolders();
            $images = $this->getImages(__DIR__ . '/../../../public/uploads', '');
        }
        
        $viewData = [
            'folder' => $folder,
            'messages' => $messages,
            'images' => $images,
            'folders' => $folders,
            'adminService' => $this->adminService
        ];
        
        $this->render('admin/upload/index', $viewData);
    }
    
    private function handleUpload(string $uploadDir): array
    {
        $messages = [];
        
        try {
            $file = $_FILES['image'];
            $fileName = basename($file['name']);
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                $messages[] = ['type' => 'error', 'text' => 'Není povoleno nahrávání tohoto typu souboru.'];
                return $messages;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) {
                $messages[] = ['type' => 'error', 'text' => 'Soubor je příliš velký. Maximální velikost je 5MB.'];
                return $messages;
            }
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $messages[] = ['type' => 'error', 'text' => 'Nepodařilo se vytvořit složku pro upload.'];
                    return $messages;
                }
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $messages[] = ['type' => 'success', 'text' => "Obrázek byl nahrán: $fileName"];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Nastala chyba při nahrávání obrázku.'];
            }
            
        } catch (\Exception $e) {
            $messages[] = ['type' => 'error', 'text' => $e->getMessage()];
        }
        
        return $messages;
    }
    
    private function getImages(string $uploadDir, string $folder): array
    {
        $images = [];
        
        if (!is_dir($uploadDir)) {
            return $images;
        }
        
        $files = array_filter(glob($uploadDir . DIRECTORY_SEPARATOR . '*'), 'is_file');
        
        foreach ($files as $file) {
            $fileName = basename($file);
            $images[] = [
                'name' => $fileName,
                'url' => '/uploads/' . ($folder ? $folder . '/' : '') . $fileName,
                'path' => $file
            ];
        }
        
        return $images;
    }
    
    private function getFolders(): array
    {
        $uploadsDir = __DIR__ . '/../../../public/uploads';
        $folders = [];
        
        if (is_dir($uploadsDir)) {
            $directories = array_filter(glob($uploadsDir . '/*'), 'is_dir');
            foreach ($directories as $dir) {
                $folderName = basename($dir);
                $folders[] = [
                    'name' => $folderName,
                    'path' => $folderName,
                    'url' => "/admin/upload?folder=" . urlencode($folderName)
                ];
            }
        }
        
        return $folders;
    }
    
    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/upload/index':
                include __DIR__ . '/../../Views/admin/upload/index.php';
                break;
        }
    }
}