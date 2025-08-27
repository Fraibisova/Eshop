<?php

namespace App\Services;

use App\Interfaces\CartServiceInterface;
use PDO;

class CartService implements CartServiceInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function aggregateCart(): array
    {
        $aggregated_cart = [];
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $cartItem) {
                $id = $cartItem['id'];
                if (!isset($aggregated_cart[$id])) {
                    $aggregated_cart[$id] = $cartItem;
                } else {
                    $aggregated_cart[$id]['quantity'] += $cartItem['quantity'];
                }
            }
        }
        return $aggregated_cart;
    }

    public function calculateCartPrice(array $aggregated_cart): float
    {
        $actualprice = 0;
        foreach ($aggregated_cart as $cartItem) {
            if (isset($cartItem['type']) && $cartItem['type'] === 'variant') {
                $actualprice += $cartItem['price'] * $cartItem['quantity'];
            } else {
                $id = $cartItem['id'];
                $sql = "SELECT * FROM items WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                $item = $stmt->fetch();
                
                $item_price = $item['price'];
                $sale_percentage = (int)($item['sale'] ?? 0);
                if ($sale_percentage > 0) {
                    $item_price = $item_price * (1 - $sale_percentage / 100);
                }
                
                $actualprice += $item_price * $cartItem['quantity'];
            }
        }
        return $actualprice;
    }

    public function addToCart(array $product, int $quantity = 1): void
    {
        $sale_percentage = (int)($product['sale'] ?? 0);
        $final_price = $sale_percentage > 0 ? 
            $product['price'] * (1 - $sale_percentage / 100) : 
            $product['price'];
        
        if (isset($_SESSION['cart'][$product['id']])) {
            $_SESSION['cart'][$product['id']]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product['id']] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $final_price, 
                'original_price' => $product['price'],
                'sale' => $sale_percentage,
                'quantity' => $quantity,
            ];
        }
    }

    public function removeFromCart(int $itemId): void
    {
        if (isset($_SESSION['cart'][$itemId])) {
            unset($_SESSION['cart'][$itemId]);
        }
    }

    public function updateCartQuantity(int $itemId, string $action): bool
    {
        if (!isset($_SESSION['cart'][$itemId])) {
            return false;
        }
        
        if ($action === 'increase') {
            $_SESSION['cart'][$itemId]['quantity']++;
        } elseif ($action === 'decrease') {
            if ($_SESSION['cart'][$itemId]['quantity'] > 1) {
                $_SESSION['cart'][$itemId]['quantity']--;
            } else {
                unset($_SESSION['cart'][$itemId]);
            }
        }
        
        return true;
    }

    public function isProductInCart(int $productId, ?int $variantId = null): bool
    {
        if (!isset($_SESSION['cart'])) {
            return false;
        }
        
        if ($variantId) {
            $cartKey = 'variant_' . $variantId;
            return isset($_SESSION['cart'][$cartKey]);
        } else {
            return isset($_SESSION['cart'][$productId]);
        }
    }

    public function getProductQuantityInCart(int $productId, ?int $variantId = null): int
    {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }
        
        if ($variantId) {
            $cartKey = 'variant_' . $variantId;
            return isset($_SESSION['cart'][$cartKey]) ? $_SESSION['cart'][$cartKey]['quantity'] : 0;
        } else {
            return isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
        }
    }

    public function calculateFreeShippingProgress(float $totalPrice, int $freeShippingLimit = 1500): array
    {
        $percentage = min(100, ($totalPrice / $freeShippingLimit) * 100);
        $remaining = $freeShippingLimit - $totalPrice;
        
        return [
            'percentage' => $percentage,
            'remaining' => max(0, $remaining),
            'qualifiesForFreeShipping' => $totalPrice >= $freeShippingLimit
        ];
    }
}