<?php

namespace App\Utils;

class MessageHelper
{
    public static function displayErrorMessages(): void
    {
        if (isset($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $value) {
                echo "<p class='red'>" . htmlspecialchars($value) . "</p>";
            }
            unset($_SESSION['errors']);
        }
    }

    public static function displaySuccessMessage(?string $message = null): void
    {
        if (isset($_SESSION['success'])) {
            echo $_SESSION['success'];
            unset($_SESSION['success']);
        } elseif ($message !== null) {
            echo "<p style='color: green;'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    public static function setSessionErrors(array $errors): void
    {
        $_SESSION["errors"] = $errors;
    }

    public static function displaySessionMessage(string $key, string $default_class = 'green'): void
    {
        if (isset($_SESSION[$key])) {
            echo "<span class='{$default_class}'>{$_SESSION[$key]}</span>";
            unset($_SESSION[$key]);
        }
    }

    public static function redirectWithMessage(string $location, string $sessionKey, string $message): void
    {
        $_SESSION[$sessionKey] = $message;
        header("Location: $location");
        exit();
    }
}