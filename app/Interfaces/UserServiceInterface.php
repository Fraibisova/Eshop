<?php

namespace App\Interfaces;

use App\Models\User;

interface UserServiceInterface
{
    public function getUserById(int $userId): ?User;
    public function getUserByEmail(string $email): ?User;
    public function updateUserProfile(int $userId, array $profileData): bool;
    public function updateUserBillingAddress(int $userId, array $addressData): bool;
    public function changeUserPassword(int $userId, string $currentPassword, string $newPassword): bool;
    public function isEmailTaken(string $email, int $excludeUserId = null): bool;
    public function authenticateUser(string $email, string $password): ?User;
    public function checkUserAuthentication(): void;
    public function getUserData(int $userId): ?array;
    public function getBillingAddress(int $userId): ?array;
    public function updateBillingAddress(int $userId, array $addressData): bool;
    public function verifyCurrentPassword(int $userId, string $password): bool;
    public function updatePassword(int $userId, string $newPassword): bool;
    public function updateProfile(int $userId, array $profileData): bool;
}