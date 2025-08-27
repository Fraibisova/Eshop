<?php

namespace App\Services;

use PDO;

class CartHelperService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function prepareCartData(array $cartItems): array
    {
        $cartData = [];
        $totalPrice = 0;

        foreach ($cartItems as $cartItem) {
            $id = $cartItem['id'];
            $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
            
            if ($is_variant) {
                $itemPrice = $cartItem['price'] * $cartItem['quantity'];
                $totalPrice += $itemPrice;
                
                $display_name = $cartItem['name'];
                if (isset($cartItem['variant_code']) && $cartItem['variant_code']) {
                    $display_name .= ' - ' . $cartItem['variant_code'];
                    if (isset($cartItem['variant_name']) && $cartItem['variant_name']) {
                        $display_name .= ' - ' . $cartItem['variant_name'];
                    }
                }
                if (isset($cartItem['stock_status']) && $cartItem['stock_status'] === 'Předobjednat') {
                    $display_name .= ' (Předobjednané)';
                }
                
                $cartData[] = [
                    'id' => $id,
                    'name' => $display_name,
                    'original_name' => $cartItem['name'],
                    'price' => $cartItem['price'],
                    'quantity' => $cartItem['quantity'],
                    'total' => $itemPrice,
                    'is_variant' => true,
                    'variant_id' => $cartItem['variant_id'] ?? null,
                    'variant_code' => $cartItem['variant_code'] ?? '',
                    'variant_name' => $cartItem['variant_name'] ?? '',
                    'stock_status' => $cartItem['stock_status'] ?? 'Skladem',
                    'image_folder' => $cartItem['image_folder'] ?? null
                ];
            } else {
                $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $sale_percentage = (int)($product['sale'] ?? 0);
                    $original_price = (float)$product['price'];
                    $discounted_price = $sale_percentage > 0 
                        ? $original_price * (1 - $sale_percentage / 100) 
                        : $original_price;
                    
                    $itemPrice = $discounted_price * $cartItem['quantity'];
                    $totalPrice += $itemPrice;
                    
                    $display_name = $product['name'];
                    if ($product['stock'] === 'Předobjednat') {
                        $display_name .= ' (Předobjednané)';
                    }
                    
                    $cartData[] = [
                        'id' => $id,
                        'name' => $display_name,
                        'original_name' => $product['name'],
                        'price' => $discounted_price,
                        'original_price' => $original_price,
                        'sale_percentage' => $sale_percentage,
                        'quantity' => $cartItem['quantity'],
                        'total' => $itemPrice,
                        'is_variant' => false,
                        'stock_status' => $product['stock'],
                        'image_folder' => $product['image_folder']
                    ];
                }
            }
        }

        return [
            'items' => $cartData,
            'total_price' => $totalPrice
        ];
    }

    public function calculateFreeShippingProgress(float $totalPrice, float $freeShippingLimit = 1500): array
    {
        if ($totalPrice >= $freeShippingLimit) {
            return [
                'percentage' => 100,
                'remaining' => 0,
                'qualified' => true,
                'amount_needed' => 0
            ];
        }

        $remaining = $freeShippingLimit - $totalPrice;
        $percentage = ($totalPrice / $freeShippingLimit) * 100;

        return [
            'percentage' => min(100, max(0, $percentage)),
            'remaining' => $remaining,
            'qualified' => false,
            'amount_needed' => $remaining
        ];
    }

    public function validateCartItems(array $cartItems): array
    {
        $validation_results = [];
        $valid_items = [];
        $total_errors = 0;

        foreach ($cartItems as $key => $cartItem) {
            $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
            
            if ($is_variant) {
                $variantService = new VariantService();
                $variant = $variantService->getVariantById($cartItem['variant_id'] ?? 0);
                
                if (!$variant) {
                    $validation_results[$key] = ['error' => 'Varianta již neexistuje'];
                    $total_errors++;
                    continue;
                }
                
                if ($variant['stock_quantity'] < $cartItem['quantity']) {
                    $validation_results[$key] = ['error' => 'Nedostatečné množství na skladě'];
                    $total_errors++;
                    continue;
                }
                
                $valid_items[$key] = $cartItem;
                $validation_results[$key] = ['valid' => true];
            } else {
                $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id AND visible = 1");
                $stmt->execute(['id' => $cartItem['id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $validation_results[$key] = ['error' => 'Produkt již není dostupný'];
                    $total_errors++;
                    continue;
                }
                
                if ($product['stock'] === 'Není skladem') {
                    $validation_results[$key] = ['error' => 'Produkt není skladem'];
                    $total_errors++;
                    continue;
                }
                
                $valid_items[$key] = $cartItem;
                $validation_results[$key] = ['valid' => true];
            }
        }

        return [
            'valid_items' => $valid_items,
            'validation_results' => $validation_results,
            'total_errors' => $total_errors,
            'is_valid' => $total_errors === 0
        ];
    }

    public function updateCartItemQuantity(string $itemKey, int $quantity): bool
    {
        if (!isset($_SESSION['cart'])) {
            return false;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$itemKey]);
            return true;
        }

        if (isset($_SESSION['cart'][$itemKey])) {
            $_SESSION['cart'][$itemKey]['quantity'] = $quantity;
            return true;
        }

        return false;
    }

    public function removeCartItem(string $itemKey): bool
    {
        if (isset($_SESSION['cart'][$itemKey])) {
            unset($_SESSION['cart'][$itemKey]);
            return true;
        }
        return false;
    }

    public function clearCart(): void
    {
        $_SESSION['cart'] = [];
    }

    public function getCartItemCount(): int
    {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return 0;
        }

        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'] ?? 1;
        }

        return $count;
    }
}