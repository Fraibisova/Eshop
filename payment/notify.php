<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    exit();
}

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}
require_once '../gopay_config.php';
include '../config.php';

$json = file_get_contents('php://input');

$data = null;

if (!empty($json)) {
    $data = json_decode($json, true);
} elseif (!empty($_POST)) {
    $data = $_POST;
} elseif (!empty($_GET)) {
    $data = $_GET;
}

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    exit('Invalid request data');
}

$paymentId = $data['id'];

try {
    $status = $gopay->getStatus($paymentId);
    
    if (!$status->hasSucceed()) {
        http_response_code(400);
        exit('Failed to get payment status');
    }
    
    $paymentState = $status->json['state'];
    $orderNumber = $status->json['order_number'] ?? null;
    
    if (!$orderNumber) {
        http_response_code(400);
        exit('Order number not found in payment data');
    }
    
    if (!isset($pdo)) {
        http_response_code(500);
        exit('Database connection error');
    }
    
    $checkStmt = $pdo->prepare("SELECT * FROM orders_user WHERE order_number = :order");
    $checkStmt->execute([':order' => $orderNumber]);
    $existingOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingOrder) {
        http_response_code(400);
        exit('Order not found in database');
    }
    
    switch ($paymentState) {
        case 'PAID':
            $stmt = $pdo->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
            $stmt->execute([':status' => 'paid', ':order' => $orderNumber]);
            http_response_code(200);
            break;
            
        case 'CANCELED':
            $stmt = $pdo->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
            $stmt->execute([':status' => 'canceled', ':order' => $orderNumber]);
            http_response_code(200);
            break;
            
        case 'TIMEOUTED':
            $stmt = $pdo->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
            $stmt->execute([':status' => 'timeouted', ':order' => $orderNumber]);
            http_response_code(200);
            break;
            
        case 'CREATED':
        case 'PAYMENT_METHOD_CHOSEN':
            http_response_code(200);
            break;
            
        default:
            http_response_code(200);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
} catch (Exception $e) {
    http_response_code(400);
    exit('Error processing payment notification');
}
?>