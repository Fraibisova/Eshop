<?php

namespace App\Interfaces;

use App\Models\User;

interface AuthServiceInterface
{
    public function login(string $email, string $password): array;
    public function register(array $userData): array;
    public function logout(): void;
    public function isLoggedIn(): bool;
    public function getCurrentUser(): ?User;
    public function sendPasswordResetEmail(string $email): array;
    public function resetPassword(string $token, string $newPassword): array;
    public function verifyEmail(string $token): array;
}