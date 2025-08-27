<?php

namespace App\Interfaces;

interface CartServiceInterface
{
    public function aggregateCart(): array;
    public function calculateCartPrice(array $aggregated_cart): float;
    public function addToCart(array $product, int $quantity = 1): void;
    public function removeFromCart(int $itemId): void;
    public function updateCartQuantity(int $itemId, string $action): bool;
    public function isProductInCart(int $productId, ?int $variantId = null): bool;
    public function getProductQuantityInCart(int $productId, ?int $variantId = null): int;
    public function calculateFreeShippingProgress(float $totalPrice, int $freeShippingLimit = 1500): array;
}