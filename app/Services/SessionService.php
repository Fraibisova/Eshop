<?php

namespace App\Services;

use App\Interfaces\SessionServiceInterface;

class SessionService implements SessionServiceInterface
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        session_unset();
    }

    public function destroy(): void
    {
        session_destroy();
    }

    public function regenerateId(bool $deleteOldSession = false): void
    {
        session_regenerate_id($deleteOldSession);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function getCart(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    public function setCart(array $cart): void
    {
        $_SESSION['cart'] = $cart;
    }

    public function addToCart(int $itemId, array $item): void
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$itemId] = $item;
    }

    public function removeFromCart(int $itemId): void
    {
        unset($_SESSION['cart'][$itemId]);
    }

    public function clearCart(): void
    {
        unset($_SESSION['cart']);
    }

    public function getUserInfo(): ?array
    {
        return $_SESSION['user_info'] ?? null;
    }

    public function setUserInfo(array $userInfo): void
    {
        $_SESSION['user_info'] = $userInfo;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_info']) && 
               !empty($_SESSION['user_info']['id']);
    }

    public function validateSessionUserInfo(): bool
    {
        return isset($_SESSION['user_info']) && 
               !empty($_SESSION['user_info']['name']) && 
               !empty($_SESSION['user_info']['email']);
    }

    public function validateSessionCart(): bool
    {
        return isset($_SESSION['cart']) && !empty($_SESSION['cart']);
    }

    public function validateSessionMethodData(): bool
    {
        return (isset($_SESSION['shipping_method']) && isset($_SESSION['payment_method'])) ||
               (isset($_SESSION['methodData']) && 
                isset($_SESSION['methodData']['shipping']) && 
                isset($_SESSION['methodData']['payment']));
    }

    public function setError(string $message): void
    {
        $_SESSION['error_message'] = $message;
    }

    public function getError(): ?string
    {
        $error = $_SESSION['error_message'] ?? null;
        unset($_SESSION['error_message']);
        return $error;
    }

    public function setSuccess(string $message): void
    {
        $_SESSION['success_message'] = $message;
    }

    public function getSuccess(): ?string
    {
        $success = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);
        return $success;
    }

    public function setErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    public function getErrors(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        return $errors;
    }

    public function all(): array
    {
        return $_SESSION;
    }
}