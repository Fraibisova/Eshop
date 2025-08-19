<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

function getMailer() {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fraibisovab@gmail.com'; 
        $mail->Password   = 'xorfixkmzsgqbaeo'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;


        return $mail;
    } catch (Exception $e) {
        throw new Exception("Mailer Error: " . $e->getMessage());
    }
}
