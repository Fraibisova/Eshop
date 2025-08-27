<?php

namespace App\Services;

use PDO;
use Exception;

class NewsletterService
{
    private PDO $db;
    private EmailService $emailService;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->emailService = new EmailService();
    }

    public function subscribeToNewsletter(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Zadaný email je neplatný.'];
        }
        
        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Děkujeme za přihlášení k newsletteru!'];
            } else {
                return ['success' => true, 'message' => 'Tento email je již přihlášen.'];
            }
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Chyba při ukládání: ' . $e->getMessage()];
        }
    }

    public function generateUnsubscribeToken(string $email): string
    {
        $configService = ConfigurationService::getInstance();
        $secret = $configService->get('newsletter.secret_key');
        return hash('sha256', $email . $secret);
    }

    public function getUnsubscribeLink(string $email, string $base_url = 'https://touchthemagic.com'): string
    {
        $token = $this->generateUnsubscribeToken($email);
        return $base_url . '/action/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;
    }

    public function verifyUnsubscribeToken(string $email, string $token): bool
    {
        return hash_equals($this->generateUnsubscribeToken($email), $token);
    }

    public function processNewsletterUnsubscribe(string $email, string $token, string $action = 'unsubscribe'): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Neplatný formát emailové adresy.'];
        }
        
        if (!$this->verifyUnsubscribeToken($email, $token)) {
            return ['success' => false, 'message' => 'Neplatný nebo vypršelý odkaz. Z bezpečnostních důvodů použijte aktuální odkaz z emailu.'];
        }
        
        try {
            if ($action === 'resubscribe') {
                $stmt = $this->db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($subscriber) {
                    $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET active = 1, unsubscribed_at = NULL WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->rowCount() > 0) {
                        return ['success' => true, 'message' => 'Váš email byl úspěšně znovu přihlášen k odběru newsletteru.', 'action' => 'resubscribe'];
                    } else {
                        return ['success' => false, 'message' => 'Email již je aktivní nebo došlo k chybě.'];
                    }
                } else {
                    $stmt = $this->db->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at, active, unsubscribed_at) VALUES (?, NOW(), 1, NULL)");
                    $stmt->execute([$email]);
                    return ['success' => true, 'message' => 'Váš email byl úspěšně přihlášen k odběru newsletteru.', 'action' => 'resubscribe'];
                }
            } else {
                $stmt = $this->db->prepare("SELECT id, email, active FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($subscriber) {
                    if ($subscriber['active'] == 1) {
                        $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET active = 0, unsubscribed_at = NOW() WHERE email = ?");
                        $stmt->execute([$email]);
                        return ['success' => true, 'message' => 'Váš email byl úspěšně odhlášen z newsletteru.', 'action' => 'unsubscribed'];
                    } else {
                        return ['success' => false, 'message' => 'Tento email již byl dříve odhlášen z newsletteru.', 'action' => 'already_unsubscribed'];
                    }
                } else {
                    return ['success' => false, 'message' => 'Email nebyl nalezen v seznamu odběratelů.'];
                }
            }
        } catch (Exception $e) {
            error_log("Chyba při zpracování unsubscribe: " . $e->getMessage());
            return ['success' => false, 'message' => 'Došlo k chybě při zpracování požadavku. Zkuste to prosím později.'];
        }
    }

    public function getNewsletterTemplate(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM newsletter_templates WHERE name='Basic' LIMIT 1");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Chyba při načítání šablony: " . $e->getMessage());
            $this->logMessage("Chyba při načítání šablony: " . $e->getMessage());
            return [];
        }
    }

    public function saveNewsletter(string $title, string $html_content, string $status, ?string $scheduled_at = null, ?int $id = null): int|false
    {
        try {
            $valid_statuses = ['draft', 'ready', 'sent', 'scheduled', 'sending', 'failed'];
            if (!in_array($status, $valid_statuses)) {
                $status = 'draft';
            }
            
            if ($id) {
                $sql = "UPDATE newsletters SET title = ?, content = ?, status = ?, scheduled_at = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$title, $html_content, $status, $scheduled_at, $id]);
                $this->logMessage("Newsletter aktualizován ID: $id, status: $status, scheduled_at: $scheduled_at");
                return $id;
            } else {
                $sql = "INSERT INTO newsletters (title, content, status, scheduled_at, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$title, $html_content, $status, $scheduled_at]);
                
                $id = $this->db->lastInsertId();
                $this->logMessage("Newsletter uložen s ID: $id, status: $status, scheduled_at: $scheduled_at");
                return $id;
            }
        } catch (Exception $e) {
            error_log("Chyba při ukládání newsletteru: " . $e->getMessage());
            $this->logMessage("Chyba při ukládání newsletteru: " . $e->getMessage());
            return false;
        }
    }

    public function sendNewsletterToSubscribers(int $newsletter_id, string $title, string $template_with_placeholders): int|false
    {
        try {
            $this->logMessage("🚀 Začínám odesílání newsletteru ID: $newsletter_id s názvem: $title");
            
            $stmt = $this->db->prepare("UPDATE newsletters SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $stmt->execute([$newsletter_id]);
            
            $stmt = $this->db->prepare("SELECT email FROM newsletter_subscribers WHERE active = 1");
            $stmt->execute();
            $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($subscribers)) {
                $this->logMessage("❌ Žádní aktivní odběratelé pro newsletter ID: $newsletter_id");
                return 0;
            }

            $this->logMessage("📧 Nalezeno " . count($subscribers) . " aktivních odběratelů");

            $sent_count = 0;
            $failed_count = 0;
            
            foreach ($subscribers as $email) {
                try {
                    $personalized_html = str_replace('{{unsubscribe}}', $this->getUnsubscribeLink($email), $template_with_placeholders);

                    $result = $this->emailService->sendEmail($email, $title, $personalized_html);
                    
                    if ($result['success']) {
                        $sent_count++;
                        $this->logMessage("✅ Úspěšně odesláno na: $email");
                    } else {
                        $failed_count++;
                        $this->logMessage("❌ Nepodařilo se odeslat na: $email - " . $result['message']);
                        
                        if ($failed_count > 5 && $sent_count == 0) {
                            $this->logMessage("⚠️ Příliš mnoho chyb na začátku - přerušujem odesílání");
                            break;
                        }
                    }
                    
                    usleep(200000); 
                    
                } catch (Exception $e) {
                    $failed_count++;
                    $this->logMessage("💥 Výjimka při odesílání na $email: " . $e->getMessage());
                }
            }

            $this->logMessage("📊 Newsletter ID: $newsletter_id dokončen. Úspěšně: $sent_count, Neúspěšně: $failed_count z celkem " . count($subscribers));
            
            return $sent_count;
        } catch (Exception $e) {
            $this->logMessage("💥 KRITICKÁ CHYBA při odesílání newsletteru ID $newsletter_id: " . $e->getMessage());
            
            try {
                $stmt = $this->db->prepare("UPDATE newsletters SET status = 'draft' WHERE id = ?");
                $stmt->execute([$newsletter_id]);
            } catch (Exception $updateError) {
                $this->logMessage("💥 Chyba při aktualizaci statusu: " . $updateError->getMessage());
            }
            
            return false;
        }
    }

    public function deleteNewsletter(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM newsletters WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Chyba při mazání newsletteru: " . $e->getMessage());
        }
    }

    public function getAllNewsletters(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM newsletters ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function checkAndSendScheduledNewsletters(): int|false
    {
        try {
            $this->logMessage("Kontrolujem naplánované newslettery");
            
            $stmt = $this->db->prepare("
                SELECT id, title, content, scheduled_at 
                FROM newsletters 
                WHERE status = 'scheduled' 
                AND scheduled_at <= NOW()
                ORDER BY scheduled_at ASC
            ");
            $stmt->execute();
            $newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($newsletters)) {
                $this->logMessage("Žádné newslettery k odeslání");
                return 0;
            }
            
            $this->logMessage("Nalezeno " . count($newsletters) . " newsletterů k odeslání");
            
            $total_sent = 0;
            foreach ($newsletters as $newsletter) {
                $this->logMessage("Zpracovávám newsletter ID: " . $newsletter['id'] . " - " . $newsletter['title']);
                
                $sent_count = $this->sendNewsletterToSubscribers($newsletter['id'], $newsletter['title'], $newsletter['content']);
                
                if ($sent_count !== false) {
                    $total_sent += $sent_count;
                }
            }
            
            return $total_sent;
            
        } catch (Exception $e) {
            $this->logMessage("CHYBA při kontrole naplánovaných newsletterů: " . $e->getMessage());
            return false;
        }
    }

    private function logMessage(string $message): void
    {
        $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
        $logFile = __DIR__ . '/../../logs/newsletter_cron.log';
        
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    }

    public function generatePreview(array $templateData, array $postData = []): string
    {
        if (empty($templateData)) {
            return '<p>Šablona nebyla nalezena</p>';
        }
        
        $template = $templateData[0]['html_template'] ?? '';
        
        $replacements = [
            '{{main_title}}' => $postData['main_title'] ?? 'Test Titulek',
            '{{subtitle}}' => $postData['subtitle'] ?? 'Test Podtitulek',
            '{{title_paragraph}}' => $postData['title_paragraph'] ?? 'Test úvod...',
            '{{text_block}}' => nl2br($postData['text_block'] ?? 'Test obsah newsletteru...'),
            '{{main_image}}' => $postData['main_image'] ?? 'https://via.placeholder.com/600x300',
            '{{shop_link}}' => $postData['shop_link'] ?? '#',
            '{{shop_text}}' => $postData['shop_text'] ?? 'Navštívit obchod',
            '{{company_name}}' => 'Touch the Magic',
            '{{website_url}}' => 'https://touchthemagic.com',
            '{{unsubscribe_url}}' => '#unsubscribe'
        ];

        $products_html = '';
        if (isset($postData['products']) && is_array($postData['products'])) {
            foreach ($postData['products'] as $product) {
                if (!empty($product['title']) || !empty($product['image'])) {
                    $products_html .= "<div class='product'>\n";
                    $products_html .= "  <img alt='produkt' src='" . htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/200x200') . "'>\n";
                    $products_html .= "  <h3>" . htmlspecialchars($product['title'] ?? 'Test Produkt') . "</h3>\n";
                    $products_html .= "  <p>" . htmlspecialchars($product['description'] ?? 'Test popis') . "</p>\n";
                    $products_html .= "  <a href='" . htmlspecialchars($product['button_link'] ?? '#') . "'>Více</a>\n";
                    $products_html .= "</div>\n";
                }
            }
        }
        
        $replacements['{{products}}'] = $products_html;
        $template = str_replace(['{{#each products}}', '{{/each}}'], '', $template);
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function generateNewsletterPreview(array $templateData, array $postData = []): string
    {
        return $this->generatePreview($templateData, $postData);
    }

    public function processNewsletterTemplate(array $templateData, array $postData): string
    {
        if (empty($templateData)) {
            return '';
        }
        
        $template_for_save = $templateData[0]['html_template'] ?? '';
        
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
}