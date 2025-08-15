<?php
session_start();
define('APP_ACCESS', true);

require_once '../config.php';
include '../lib/function_action.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_newsletter'])) {
    $email = trim($_POST['email_newsletter']);
    
    $result = subscribeToNewsletter($pdo, $email);
    $_SESSION['newsletter_status'] = $result['message'];
    
    header('Location: ../index.php#newsletter');
    exit();
}
?>
