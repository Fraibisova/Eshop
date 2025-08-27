<?php

namespace App\Services;

class AuthorizationService
{
    public function checkAdminRole(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 10) {
            header('location: index.php');
            exit();
        }
    }

    public function hasRole(string $role): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    public function hasAdminAccess(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] == 10;
    }

    public function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            header('location: /');
            exit();
        }
    }
}