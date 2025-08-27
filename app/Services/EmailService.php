<?php

namespace App\Services;

use App\Interfaces\EmailServiceInterface;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService implements EmailServiceInterface
{
    private $mailer;
    private ConfigurationService $config;

    public function __construct()
    {
        $this->config = ConfigurationService::getInstance();
        $this->mailer = $this->getMailer();
    }

    private function getMailer()
    {
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        }
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer(true);
            
            try {
                $mailConfig = $this->config->getMailConfig();
                
                $mail->isSMTP();
                $mail->Host = $mailConfig['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig['username'];
                $mail->Password = $mailConfig['password'];
                $mail->SMTPSecure = $mailConfig['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = $mailConfig['port'];
            } catch (\Exception $e) {
                error_log("SMTP configuration error: " . $e->getMessage());
            }
            
            return $mail;
        } else {
            if (file_exists(__DIR__ . '/../../mailer.php')) {
                if (!function_exists('getMailer')) {
                    require_once __DIR__ . '/../../mailer.php';
                }
                return getMailer();
            }
            
            return null;
        }
    }

    public function sendEmail(string $recipient, string $subject, string $body, ?string $attachmentPath = null): array
    {
        if ($this->mailer === null) {
            return ['success' => false, 'message' => 'Email service not available - PHPMailer not found'];
        }
        
        try {
            $mailConfig = $this->config->getMailConfig();
            $this->mailer->setFrom($mailConfig['from']['address'], $mailConfig['from']['name']);
            $this->mailer->CharSet = 'UTF-8'; 
            $this->mailer->Encoding = 'base64';

            $this->mailer->addAddress($recipient);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body;

            if ($attachmentPath && file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath);
            }

            $this->mailer->send();
            return ['success' => true, 'message' => 'E-mail byl úspěšně odeslán.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "E-mail nemohl být odeslán. Chyba: " . $e->getMessage()];
        }
    }

    public function addToNewsletterSubscribers(string $email): bool
    {
        $db = DatabaseService::getInstance()->getConnection();
        
        try {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = :email");
            $checkStmt->execute(['email' => $email]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return true;
            }
            
            $insertStmt = $db->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at, active) VALUES (:email, NOW(), 1)");
            $insertStmt->execute(['email' => $email]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Chyba při přidávání do newsletter_subscribers: " . $e->getMessage());
            return false;
        }
    }
}