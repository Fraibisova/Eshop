<?php
    define('APP_ACCESS', true);

    session_start();
    include "../config.php";
    include "../lib/function_admin.php";
    checkAdminRole();
    
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        try {
            deleteProduct($pdo, (int)$_GET['id']);
            header("location: edit_goods.php");
            exit();
        } catch (Exception $e) {
            displayErrorMessage($e->getMessage());
        }
    } else {
        displayErrorMessage("Neplatné ID.");
    }


?>