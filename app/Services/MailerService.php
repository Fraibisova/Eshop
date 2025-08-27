<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    private static ?MailerService $instance = null;
    private array $config;
    private ConfigurationService $configService;

    private function __construct()
    {
        $this->configService = ConfigurationService::getInstance();
        $this->config = $this->configService->getMailConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['port'];
            $mail->CharSet = 'UTF-8';

            return $mail;
        } catch (Exception $e) {
            throw new Exception("Mailer Error: " . $e->getMessage());
        }
    }

    public function sendEmail(string $to, string $subject, string $body, bool $isHTML = true): bool
    {
        try {
            $mail = $this->getMailer();
            
            $mail->setFrom($this->config['from']['address'], $this->config['from']['name']);
            $mail->addAddress($to);
            
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendThankYouEmail(string $email, string $paymentStatus, ?string $orderNumber, string $date, float $totalAmount, string $paymentMethod, ?string $invoicePdf, $pdo): bool
    {
        try {
            $subject = "Potvrzení objednávky " . ($orderNumber ? "#$orderNumber" : "");
            
            $body = $this->buildThankYouEmailBody($paymentStatus, $orderNumber, $date, $totalAmount, $paymentMethod, $pdo);
            
            $mail = $this->getMailer();
            $mail->setFrom($this->config['from']['address'], $this->config['from']['name']);
            $mail->addAddress($email);
            
            if ($invoicePdf && file_exists($invoicePdf)) {
                $mail->addAttachment($invoicePdf);
            }
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Thank you email failed: " . $e->getMessage());
            return false;
        }
    }

    private function buildThankYouEmailBody(string $paymentStatus, ?string $orderNumber, string $date, float $totalAmount, string $paymentMethod, $pdo): string
    {
        $statusText = match($paymentStatus) {
            'paid' => 'Vaše objednávka byla úspěšně zaplacena.',
            'pending' => 'Vaše objednávka čeká na zaplacení.',
            'canceled' => 'Vaše objednávka byla zrušena.',
            'timeouted' => 'Čas pro zaplacení objednávky vypršel.',
            default => 'Status vaší objednávky: ' . $paymentStatus
        };

        $html = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto;'>
                <h2>Děkujeme za vaši objednávku!</h2>
                <p><strong>$statusText</strong></p>
                
                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0;'>
                    <h3>Detaily objednávky:</h3>
                    <p><strong>Číslo objednávky:</strong> " . ($orderNumber ?: 'Bude přiděleno') . "</p>
                    <p><strong>Datum:</strong> $date</p>
                    <p><strong>Celková částka:</strong> $totalAmount Kč</p>
                    <p><strong>Způsob platby:</strong> $paymentMethod</p>
                </div>
                
                <p>V případě dotazů nás neváhejte kontaktovat.</p>
                <p>S pozdravem,<br>Tým Touch the Magic</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    public function sendNewsletterEmail(string $email, string $subject, string $content): bool
    {
        return $this->sendEmail($email, $subject, $content, true);
    }
}