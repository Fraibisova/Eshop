<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use App\Services\NewsletterService;
use PDO;

class NewsletterController
{
    private AdminService $adminService;
    private NewsletterService $newsletterService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->adminService = new AdminService();
        $this->adminService->checkAdminRole();
        $this->newsletterService = new NewsletterService();
    }

    public function index(): void
    {
        $statusFilter = $_GET['status'] ?? '';

        if (!empty($statusFilter)) {
            $newsletters = $this->adminService->executePaginatedQuery(
                "SELECT * FROM newsletters WHERE status = ? ORDER BY created_at DESC", 
                [$statusFilter], 
                0, 
                1000
            );
        } else {
            $newsletters = $this->adminService->executePaginatedQuery(
                "SELECT * FROM newsletters ORDER BY created_at DESC", 
                [], 
                0, 
                1000
            );
        }

        $this->render('admin/newsletter/index', [
            'newsletters' => $newsletters,
            'statusFilter' => $statusFilter,
            'adminService' => $this->adminService
        ]);
    }

    public function create(): void
    {
        $template_html = '';
        $message = '';
        $templateData = $this->newsletterService->getNewsletterTemplate();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview') {
            $preview_html = $this->newsletterService->generatePreview($templateData, $_POST);
            file_put_contents(__DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html', $preview_html);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handleNewsletterSubmission($templateData);
            $message = $result['message'];
            $template_html = $result['template_html'];
        } else {
            $template_html = $this->newsletterService->generatePreview($templateData);
            file_put_contents(__DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html', $template_html);
        }

        $this->render('admin/newsletter/create', [
            'message' => $message,
            'template_html' => $template_html,
            'adminService' => $this->adminService
        ]);
    }

    public function edit(): void
    {
        $newsletter = [
            'id' => null,
            'main_title' => '',
            'subtitle' => '',
            'title_paragraph' => '',
            'main_image' => '',
            'text_block' => '',
            'shop_link' => '',
            'shop_text' => '',
            'status' => 'draft',
            'scheduled_at' => ''
        ];

        if (isset($_GET['edit'])) {
            $newsletter = $this->loadNewsletter((int)$_GET['edit']);
        }

        $scheduled_sent = $this->newsletterService->checkAndSendScheduledNewsletters();
        $template_html = '';
        $message = '';
        $templateData = $this->newsletterService->getNewsletterTemplate();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview') {
            $preview_html = $this->newsletterService->generateNewsletterPreview($templateData, $_POST);
            file_put_contents(__DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html', $preview_html);
            exit(); 
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handleNewsletterEdit($templateData, $newsletter);
            $message = $result['message'];
            $template_html = $result['template_html'];
        } else {
            $template_html = $this->newsletterService->generateNewsletterPreview($templateData, $_POST ?? []);
            
            $previewFile = __DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html';
            file_put_contents($previewFile, $template_html);
        }

        if ($scheduled_sent > 0) {
            $message = "Automaticky odesláno $scheduled_sent naplánovaných newsletterů. " . ($message ?? '');
        }

        $this->render('admin/newsletter/edit', [
            'newsletter' => $newsletter,
            'message' => $message,
            'template_html' => $template_html,
            'adminService' => $this->adminService
        ]);
    }

    public function delete(): void
    {
        if (isset($_GET['delete'])) {
            try {
                $this->newsletterService->deleteNewsletter((int)$_GET['delete']);
                header('Location: /admin/newsletter');
                exit;
            } catch (\Exception $e) {
                echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    private function loadNewsletter(int $id): array
    {
        $newsletter = [
            'id' => null,
            'main_title' => '',
            'subtitle' => '',
            'title_paragraph' => '',
            'main_image' => '',
            'text_block' => '',
            'shop_link' => '',
            'shop_text' => '',
            'status' => 'draft',
            'scheduled_at' => ''
        ];

        $db = $this->adminService->getDatabase();
        $stmt = $db->prepare("SELECT * FROM newsletters WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $newsletter['id'] = $row['id'];
            $newsletter['main_title'] = $row['title'];
            $newsletter['status'] = $row['status'];
            $newsletter['scheduled_at'] = $row['scheduled_at'];
            
            $content = $row['content'] ?? '';
            if (!empty($content)) {

                $newsletter['subtitle'] = $this->extractFromContent($content, 'subtitle') ?? '';
                $newsletter['title_paragraph'] = $this->extractFromContent($content, 'title_paragraph') ?? '';
                $newsletter['main_image'] = $this->extractFromContent($content, 'main_image') ?? '';
                $newsletter['text_block'] = $this->extractFromContent($content, 'text_block') ?? '';
                $newsletter['shop_link'] = $this->extractFromContent($content, 'shop_link') ?? '';
                $newsletter['shop_text'] = $this->extractFromContent($content, 'shop_text') ?? '';
            }
        }

        return $newsletter;
    }

    private function extractFromContent(string $content, string $field): ?string
    {

        $patterns = [
            'subtitle' => '/<h2[^>]*>(.*?)<\/h2>/s',
            'title_paragraph' => '/<p[^>]*>(.*?)<\/p>/s',
            'main_image' => '/src=["\']([^"\']*)["\']/',
            'text_block' => '/<div[^>]*class=["\']?text-block["\']?[^>]*>(.*?)<\/div>/s',
            'shop_link' => '/href=["\']([^"\']*)["\']/',
            'shop_text' => '/<a[^>]*>(.*?)<\/a>/s'
        ];
        
        if (isset($patterns[$field])) {
            if (preg_match($patterns[$field], $content, $matches)) {
                return strip_tags(trim($matches[1] ?? ''));
            }
        }
        
        return null;
    }

    private function handleNewsletterSubmission(array $templateData): array
    {
        $title = $_POST['main_title'] ?? '';
        $action = $_POST['action'] ?? 'save';
        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        
        if (empty($templateData)) {
            return [
                'message' => "Chyba: Šablona 'Basic' nebyla nalezena v tabulce newsletter_templates",
                'template_html' => ''
            ];
        }

        $template_html = $this->newsletterService->generatePreview($templateData, $_POST);
        
        if ($action !== 'preview') {
            $template_for_save = $this->processTemplate($templateData, $_POST);
            
            switch ($action) {
                case 'save':
                    $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, $status, $scheduled_at);
                    $message = $newsletter_id ? 
                        "Newsletter byl úspěšně uložen (ID: $newsletter_id) se stavem: $status" : 
                        "Chyba při ukládání newsletteru";
                    break;
                    
                case 'send_now':
                    $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, 'sent', null);
                    if ($newsletter_id) {
                        $sent_count = $this->newsletterService->sendNewsletterToSubscribers($newsletter_id, $title, $template_for_save);
                        $message = ($sent_count !== false) ? 
                            "Newsletter byl odeslán $sent_count odběratelům" : 
                            "Chyba při odesílání newsletteru";
                    } else {
                        $message = "Chyba při ukládání newsletteru před odesláním";
                    }
                    break;
                    
                default:
                    $message = "Neznámá akce";
            }
        }
        
        file_put_contents(__DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html', $template_html);
        
        return [
            'message' => $message ?? '',
            'template_html' => $template_html
        ];
    }

    private function handleNewsletterEdit(array $templateData, array $newsletter): array
    {
        $title = $_POST['main_title'] ?? '';
        $action = $_POST['action'] ?? 'save';
        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        $id = $_POST['id'] ?? null;
        
        if (empty($templateData)) {
            return [
                'message' => "Chyba: Šablona 'Basic' nebyla nalezena v tabulce newsletter_templates",
                'template_html' => ''
            ];
        }

        $template_html = $this->newsletterService->generateNewsletterPreview($templateData, $_POST);
        
        if ($action !== 'preview') {
            $template_for_save = $this->newsletterService->processNewsletterTemplate($templateData, $_POST);
            
            switch ($action) {
                case 'save':
                    $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, $status, $scheduled_at, $id);
                    if ($newsletter_id) {
                        $message = "Newsletter byl úspěšně " . ($id ? 'aktualizován' : 'uložen') . " (ID: $newsletter_id)";
                        if (!$id) {
                            header('Location: /admin/newsletter/edit?edit=' . $newsletter_id);
                            exit;
                        }
                    } else {
                        $message = "Chyba při ukládání newsletteru";
                    }
                    break;

                case 'schedule':
                    $message = $this->handleScheduling($title, $template_for_save, $scheduled_at, $id);
                    break;
                    
                case 'send_now':
                    $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, 'sending', null, $id);
                    if ($newsletter_id) {
                        $sent_count = $this->newsletterService->sendNewsletterToSubscribers($newsletter_id, $title, $template_for_save);
                        $message = ($sent_count !== false) ? 
                            "Newsletter byl odeslán $sent_count odběratelům" : 
                            "Chyba při odesílání newsletteru";
                    } else {
                        $message = "Chyba při ukládání newsletteru před odesláním";
                    }
                    break;
            }
        }
        
        file_put_contents(__DIR__ . '/../../../public/admin/newsletter/newsletter_preview.html', $template_html);
        
        return [
            'message' => $message ?? '',
            'template_html' => $template_html
        ];
    }

    private function handleScheduling(string $title, string $template_for_save, ?string $scheduled_at, ?string $id): string
    {
        if (empty($scheduled_at)) {
            return "Pro naplánování musíte zadat datum a čas";
        }

        $scheduled_timestamp = strtotime($scheduled_at);
        $current_timestamp = time();
        
        if ($scheduled_timestamp <= $current_timestamp) {
            $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, 'sending', $scheduled_at, $id);
            if ($newsletter_id) {
                $sent_count = $this->newsletterService->sendNewsletterToSubscribers($newsletter_id, $title, $template_for_save);
                return ($sent_count !== false) ? 
                    "Naplánovaný čas již prošel - newsletter byl odeslán okamžitě $sent_count odběratelům" : 
                    "Chyba při okamžitém odesílání newsletteru";
            } else {
                return "Chyba při ukládání newsletteru";
            }
        } else {
            $newsletter_id = $this->newsletterService->saveNewsletter($title, $template_for_save, 'scheduled', $scheduled_at, $id);
            return $newsletter_id ? 
                "Newsletter byl naplánován na odeslání: " . date('d.m.Y H:i', strtotime($scheduled_at)) : 
                "Chyba při plánování newsletteru";
        }
    }

    private function processTemplate(array $templateData, array $postData): string
    {
        $template_for_save = $templateData[0]['html_template'];
        
        $title_save = $postData['main_title'] ?? '';
        $subtitle_save = $postData['subtitle'] ?? '';
        $title_paragraph_save = $postData['title_paragraph'] ?? '';
        $text_block_save = $postData['text_block'] ?? '';
        $main_image_save = $postData['main_image'] ?? '';
        $shop_link_save = $postData['shop_link'] ?? '#';
        $shop_text_save = $postData['shop_text'] ?? 'Navštívit obchod';
        
        $template_for_save = str_replace(['{{main_title}}', 'Ahoj'], htmlspecialchars($title_save), $template_for_save);
        $template_for_save = str_replace('{{subtitle}}', htmlspecialchars($subtitle_save), $template_for_save);
        $template_for_save = str_replace('{{title_paragraph}}', htmlspecialchars($title_paragraph_save), $template_for_save);
        $template_for_save = str_replace('{{text_block}}', nl2br(htmlspecialchars($text_block_save)), $template_for_save);
        $template_for_save = str_replace('{{main_image}}', htmlspecialchars($main_image_save), $template_for_save);
        $template_for_save = str_replace('{{shop_link}}', htmlspecialchars($shop_link_save), $template_for_save);
        $template_for_save = str_replace('{{shop_text}}', htmlspecialchars($shop_text_save), $template_for_save);
        
        $products_html_save = '';
        if (isset($postData['products']) && is_array($postData['products'])) {
            foreach ($postData['products'] as $product) {
                if (!empty($product['title']) || !empty($product['image'])) {
                    $products_html_save .= "<div class='product'>\n";
                    $products_html_save .= "  <img alt='produkt' src='" . htmlspecialchars($product['image'] ?? '') . "'>\n";
                    $products_html_save .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info'>" . htmlspecialchars($product['title'] ?? '') . "</a>\n";
                    $products_html_save .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info des'>" . htmlspecialchars($product['description'] ?? '') . "</a>\n";
                    $products_html_save .= "  <a class='more' href='".htmlspecialchars($product['button_link'] ?? '#')."'>Více</a>\n";
                    $products_html_save .= "</div>\n";
                }
            }
        }
        
        $template_for_save = str_replace(['{{#each products}}', '{{/each}}'], '', $template_for_save);
        $template_for_save = str_replace('{{products}}', $products_html_save, $template_for_save);
        
        return $template_for_save;
    }

    private function render(string $template, array $data = []): void
    {
        extract($data);
        
        switch ($template) {
            case 'admin/newsletter/index':
                include __DIR__ . '/../../Views/admin/newsletter/index.php';
                break;
            case 'admin/newsletter/create':
                include __DIR__ . '/../../Views/admin/newsletter/create.php';
                break;
            case 'admin/newsletter/edit':
                include __DIR__ . '/../../Views/admin/newsletter/edit.php';
                break;
        }
    }
}