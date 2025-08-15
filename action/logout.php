<?php
define('APP_ACCESS', true);

session_start();
session_destroy();
header("Location: ../index.php");
exit();
?>