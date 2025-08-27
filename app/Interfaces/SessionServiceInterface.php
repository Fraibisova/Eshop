<?php

namespace App\Interfaces;

interface SessionServiceInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function clear(): void;
    public function destroy(): void;
    public function regenerateId(bool $deleteOldSession = false): void;
    
    public function flash(string $key, mixed $value): void;
    public function getFlash(string $key, mixed $default = null): mixed;
    public function hasFlash(string $key): bool;
    
    public function getCart(): array;
    public function setCart(array $cart): void;
    public function addToCart(int $itemId, array $item): void;
    public function removeFromCart(int $itemId): void;
    public function clearCart(): void;
    
    public function getUserInfo(): ?array;
    public function setUserInfo(array $userInfo): void;
    public function isLoggedIn(): bool;
    
    public function validateSessionUserInfo(): bool;
    public function validateSessionCart(): bool;
    public function validateSessionMethodData(): bool;
    
    public function setError(string $message): void;
    public function getError(): ?string;
    public function setSuccess(string $message): void;
    public function getSuccess(): ?string;
    public function setErrors(array $errors): void;
    public function getErrors(): array;
    
    public function all(): array;
}