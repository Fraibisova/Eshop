<?php

namespace App\Interfaces;

interface ProductServiceInterface
{
    public function getProductById(int $id): ?array;
    public function getProducts(array $filters = [], int $limit = 50, int $offset = 0): array;
    public function createProduct(array $productData): int;
    public function updateProduct(int $id, array $productData): bool;
    public function deleteProduct(int $id): bool;
    public function getProductsByCategory(string $category, int $limit = 50): array;
    public function searchProducts(string $query, int $limit = 50): array;
    public function getFeaturedProducts(int $limit = 10): array;
    public function getRandomProducts(int $limit = 4): array;
}