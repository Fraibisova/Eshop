<?php 
define('APP_ACCESS', true);
session_start();
include "../config.php";
include "../lib/function_admin.php";
checkAdminRole();

if (isset($_GET['delete'])) {
    try {
        deleteNewsletter($pdo, (int)$_GET['delete']);
        header('Location: newsletter.php');
        exit;
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}
?>