<?php

namespace App\Services;

use PDO;
use Exception;

class VariantService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getProductVariants(int $itemId): array
    {
        try {
            $sql = "SELECT * FROM product_variants 
                    WHERE parent_item_id = :item_id AND is_active = TRUE 
                    ORDER BY variant_order ASC, variant_code ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching variants: " . $e->getMessage());
            return [];
        }
    }

    public function getVariantById(int $variantId): ?array
    {
        try {
            $sql = "SELECT pv.*, i.name as product_name, i.price as base_price, i.sale 
                    FROM product_variants pv 
                    JOIN items i ON pv.parent_item_id = i.id 
                    WHERE pv.id = :variant_id AND pv.is_active = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['variant_id' => $variantId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Error fetching variant: " . $e->getMessage());
            return null;
        }
    }

    public function getVariantsWithPricing(int $itemId): array
    {
        try {
            $sql = "SELECT 
                        pv.*,
                        i.price as base_price,
                        i.sale,
                        CASE 
                            WHEN pv.price_override IS NOT NULL THEN pv.price_override
                            ELSE i.price + COALESCE(pv.price_modifier, 0)
                        END as final_price,
                        CASE 
                            WHEN i.sale > 0 AND pv.price_override IS NULL THEN 
                                (i.price + COALESCE(pv.price_modifier, 0)) * (1 - i.sale / 100)
                            WHEN i.sale > 0 AND pv.price_override IS NOT NULL THEN 
                                pv.price_override * (1 - i.sale / 100)
                            ELSE 
                                CASE 
                                    WHEN pv.price_override IS NOT NULL THEN pv.price_override
                                    ELSE i.price + COALESCE(pv.price_modifier, 0)
                                END
                        END as discounted_price
                    FROM product_variants pv
                    JOIN items i ON pv.parent_item_id = i.id
                    WHERE pv.parent_item_id = :item_id AND pv.is_active = TRUE
                    ORDER BY pv.variant_order, pv.variant_code";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching variants with pricing: " . $e->getMessage());
            return [];
        }
    }

    public function hasVariants(int $itemId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM product_variants 
                    WHERE parent_item_id = :item_id AND is_active = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function calculateVariantPrice(float $basePrice, array $variant): float
    {
        if (!empty($variant['price_override'])) {
            return (float)$variant['price_override'];
        }
        
        $modifier = (float)($variant['price_modifier'] ?? 0);
        return $basePrice + $modifier;
    }

    public function calculateVariantDiscountedPrice(float $basePrice, array $variant, int $salePercentage = 0): float
    {
        $finalPrice = $this->calculateVariantPrice($basePrice, $variant);
        
        if ($salePercentage > 0) {
            return $finalPrice * (1 - $salePercentage / 100);
        }
        
        return $finalPrice;
    }

    public function addVariantToCart(int $variantId, int $quantity = 1): bool
    {
        $variant = $this->getVariantById($variantId);
        if (!$variant) {
            throw new Exception("Varianta neexistuje");
        }
        
        if ($variant['stock_status'] === 'Není skladem') {
            throw new Exception("Varianta nie je dostupná");
        }
        
        if ($variant['stock_quantity'] < $quantity) {
            throw new Exception("Nedostatečné množství na skladě");
        }
        
        $finalPrice = $this->calculateVariantPrice($variant['base_price'], $variant);
        
        $salePercentage = (int)($variant['sale'] ?? 0);
        if ($salePercentage > 0) {
            $finalPrice = $finalPrice * (1 - $salePercentage / 100);
        }
        
        $cartKey = 'variant_' . $variantId;
        
        if (isset($_SESSION['cart'][$cartKey])) {
            $newQuantity = $_SESSION['cart'][$cartKey]['quantity'] + $quantity;
            
            if ($variant['stock_quantity'] < $newQuantity) {
                throw new Exception("Nedostatečné množství na skladě pro požadované množství");
            }
            
            $_SESSION['cart'][$cartKey]['quantity'] = $newQuantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'type' => 'variant',
                'id' => $variant['parent_item_id'],
                'variant_id' => $variantId,
                'variant_code' => $variant['variant_code'],
                'name' => $variant['product_name'] . ' - varianta ' . $variant['variant_code'],
                'price' => $finalPrice,
                'original_price' => $this->calculateVariantPrice($variant['base_price'], $variant),
                'quantity' => $quantity,
                'image' => $variant['primary_image'] ?? '',
                'stock_status' => $variant['stock_status']
            ];
        }
        
        return true;
    }

    public function renderProductVariants(int $itemId, ?int $selectedVariantId = null): string
    {
        $variants = $this->getVariantsWithPricing($itemId);
        if (empty($variants)) {
            return '';
        }
        
        $html = '<div class="product-variants">
                    <h3>Dostupné varianty:</h3>
                    <div class="variants-container">';
        
        foreach ($variants as $variant) {
            $isSelected = $selectedVariantId == $variant['id'];
            $isAvailable = $variant['stock_status'] === 'Skladem' && $variant['stock_quantity'] > 0;
            
            $html .= '<div class="variant-option ' . ($isSelected ? 'selected' : '') . ' ' . (!$isAvailable ? 'unavailable' : '') . '" 
                           data-variant-id="' . $variant['id'] . '"
                           data-price="' . $variant['discounted_price'] . '"
                           data-original-price="' . $variant['final_price'] . '"
                           onclick="selectVariant(' . $variant['id'] . ')">';
            
            if (!empty($variant['primary_image'])) {
                $html .= '<div class="variant-image">
                            <img src="' . htmlspecialchars($variant['primary_image']) . '" 
                                 alt="Varianta ' . htmlspecialchars($variant['variant_code']) . '">
                          </div>';
            }
            
            $html .= '<div class="variant-info">
                        <div class="variant-header">
                            <span class="variant-code">Varianta ' . htmlspecialchars($variant['variant_code']) . '</span>';
            
            if (!empty($variant['variant_name'])) {
                $html .= '<span class="variant-name">' . htmlspecialchars($variant['variant_name']) . '</span>';
            }
            
            $html .= '</div>';
            
            $html .= '<div class="variant-price">';
            if ($variant['final_price'] != $variant['discounted_price']) {
                $html .= '<span class="original-price">' . number_format($variant['final_price'], 0, ',', ' ') . ' Kč</span>';
            }
            $html .= '<span class="final-price">' . number_format($variant['discounted_price'], 0, ',', ' ') . ' Kč</span>';
            $html .= '</div>';
            
            $html .= '<div class="variant-stock">
                        <span class="stock-status ' . strtolower(str_replace(' ', '-', $variant['stock_status'])) . '">
                            ' . htmlspecialchars($variant['stock_status']) . '
                        </span>';
            
            if ($variant['stock_status'] === 'Skladem') {
                $html .= '<span class="stock-quantity">(' . $variant['stock_quantity'] . ' ks)</span>';
            }
            
            $html .= '</div>';
            
            if (!empty($variant['differences'])) {
                $html .= '<div class="variant-differences">' . htmlspecialchars($variant['differences']) . '</div>';
            }
            
            if (!empty($variant['color']) || !empty($variant['dimensions'])) {
                $html .= '<div class="variant-properties">';
                if (!empty($variant['color'])) {
                    $html .= '<span class="property">Barva: ' . htmlspecialchars($variant['color']) . '</span>';
                }
                if (!empty($variant['dimensions'])) {
                    $html .= '<span class="property">Rozměry: ' . htmlspecialchars($variant['dimensions']) . '</span>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div></div>';
        }
        
        $html .= '</div>
                  <input type="hidden" id="selected-variant-id" name="variant_id" value="' . ($selectedVariantId ?: '') . '">
                </div>';
        
        return $html;
    }

    public function addProductVariant(array $data): int|false
    {
        try {
            $sql = "INSERT INTO product_variants (
                        parent_item_id, variant_code, variant_name, price_modifier, 
                        price_override, stock_quantity, stock_status, primary_image,
                        variant_description, differences, dimensions, color, material,
                        variant_order, is_featured
                    ) VALUES (
                        :parent_item_id, :variant_code, :variant_name, :price_modifier,
                        :price_override, :stock_quantity, :stock_status, :primary_image,
                        :variant_description, :differences, :dimensions, :color, :material,
                        :variant_order, :is_featured
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'parent_item_id' => $data['parent_item_id'],
                'variant_code' => $data['variant_code'],
                'variant_name' => $data['variant_name'] ?? null,
                'price_modifier' => $data['price_modifier'] ?? 0,
                'price_override' => !empty($data['price_override']) ? $data['price_override'] : null,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'stock_status' => $data['stock_status'] ?? 'Skladem',
                'primary_image' => $data['primary_image'] ?? null,
                'variant_description' => $data['variant_description'] ?? null,
                'differences' => $data['differences'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'color' => $data['color'] ?? null,
                'material' => $data['material'] ?? null,
                'variant_order' => $data['variant_order'] ?? 0,
                'is_featured' => isset($data['is_featured']) ? 1 : 0
            ]);
            
            if ($result) {
                $variantId = $this->db->lastInsertId();
                $this->updateProductHasVariants($data['parent_item_id'], true);
                return $variantId;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Error adding variant: " . $e->getMessage());
            throw new Exception("Chyba při přidávání varianty: " . $e->getMessage());
        }
    }

    public function getVariantPriceRange(int $itemId): ?array
    {
        $variants = $this->getVariantsWithPricing($itemId);
        
        if (empty($variants)) {
            return null;
        }
        
        $prices = array_column($variants, 'discounted_price');
        
        return [
            'min' => min($prices),
            'max' => max($prices),
            'count' => count($variants)
        ];
    }

    private function updateProductHasVariants(int $itemId, bool $hasVariants): bool
    {
        try {
            $sql = "UPDATE items SET has_variants = :has_variants WHERE id = :item_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'item_id' => $itemId,
                'has_variants' => $hasVariants ? 1 : 0
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating has_variants flag: " . $e->getMessage());
            return false;
        }
    }
}