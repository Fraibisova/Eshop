<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    exit();
}

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}
session_start();
include '../config.php'; 
include '../lib/function.php';
include '../lib/function_action.php'; 

date_default_timezone_set('Europe/Prague');

$email = $_POST["email_send"] ?? null;

if (!$email) {
    echo "Chyba: Nebyl zadán e-mail.";
    exit();
}

$token = generateResetToken();

if (saveResetToken($db, $email, $token)) {
    require "../mailer.php";
    $emailBody = newsletter($token);
    $result = sendEmail($email, "Resetování hesla", $emailBody);
    $_SESSION['email'] = $result['message'];
} else {
    $_SESSION['email'] = "Email nebyl nalezen";
}
header('location: forgot_password.php');
exit();