<?php

namespace App\Services;

use PDO;
use Exception;

class OrderService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function generateNextOrderNumber(): int
    {
        $query = $this->db->query("SELECT MAX(order_number) as max_order FROM orders_user");
        $maxOrder = $query->fetch(PDO::FETCH_ASSOC);
        return ($maxOrder['max_order'] ?? 0) + 1;
    }

    public function validateOrderNumber(int $orderNumber): bool
    {
        $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders_user WHERE order_number = :order_number");
        $checkStmt->execute(['order_number' => $orderNumber]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        return $exists['count'] == 0;
    }

    public function processCartToOrder(array $aggregated_cart, int $orderNumber): float
    {
        $totalPrice = 0;
        
        foreach ($aggregated_cart as $cartItem) {
            $id = $cartItem['id'];
            $quantity = $cartItem['quantity'];
            $isVariant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
            $variantId = null;

            if ($isVariant) {
                $variantId = $cartItem['variant_id'] ?? null;
                $itemPrice = $cartItem['price'] * $quantity;
                $totalPrice += $itemPrice;
                
                $isPreorder = 0;
                if (isset($cartItem['stock_status']) && $cartItem['stock_status'] === 'Předobjednat') {
                    $isPreorder = 1;
                }
                
                $parentId = $cartItem['parent_item_id'] ?? $id;
                
            } else {
                $sql = "SELECT * FROM items WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                $item = $stmt->fetch();

                if (!$item) continue;
                
                $itemPrice = $item['price'] * $quantity; 
                $totalPrice += $itemPrice;
                $isPreorder = ($item['stock'] === 'Předobjednat') ? 1 : 0;
                $parentId = $id;
            }

            $insertSql = "INSERT INTO orders_items (id_product, variant_id, order_number, count, is_preorder) VALUES (:id_product, :variant_id, :order_number, :count, :is_preorder)";
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute([
                'id_product' => $parentId,
                'variant_id' => $variantId,
                'order_number' => $orderNumber,
                'count' => $quantity,
                'is_preorder' => $isPreorder
            ]);
        }
        
        return $totalPrice;
    }

    public function getOrderItemsWithVariants(int $orderNumber): array
    {
        try {
            $sql = "
                SELECT 
                    oi.id_product,
                    oi.variant_id,
                    oi.count,
                    oi.is_preorder,
                    i.name as product_name,
                    i.price as product_price,
                    i.stock as product_stock,
                    pv.variant_code,
                    pv.variant_name,
                    pv.price_override,
                    pv.price_modifier,
                    pv.stock_status as variant_stock_status
                FROM orders_items oi
                LEFT JOIN items i ON oi.id_product = i.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE oi.order_number = :order_number
                ORDER BY oi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':order_number' => $orderNumber]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($items as $item) {
                $displayName = $item['product_name'];
                $price = $item['product_price'];
                
                if ($item['variant_id']) {
                    if ($item['variant_code']) {
                        $displayName .= ' - ' . $item['variant_code'];
                        if ($item['variant_name']) {
                            $displayName .= ' - ' . $item['variant_name'];
                        }
                    }
                    
                    if ($item['price_override']) {
                        $price = $item['price_override'];
                    } elseif ($item['price_modifier']) {
                        $price = $item['product_price'] + $item['price_modifier'];
                    }
                    
                    $stock_status = $item['variant_stock_status'] ?? $item['product_stock'];
                } else {
                    $stock_status = $item['product_stock'];
                }
                
                if ($stock_status === 'Předobjednat') {
                    $displayName .= ' (Předobjednané)';
                }
                
                $result[] = [
                    'name' => $displayName,
                    'price' => $price,
                    'count' => $item['count'],
                    'is_preorder' => $item['is_preorder'],
                    'total_price' => $price * $item['count']
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Chyba při získávání položek objednávky: " . $e->getMessage());
            return [];
        }
    }

    public function getOrderPaymentStatus(int $order_number): array
    {
        try {
            $stmt = $this->db->prepare("SELECT payment_status, invoice_pdf_path FROM orders_user WHERE order_number = :order_number");
            $stmt->execute([':order_number' => $order_number]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                return [
                    'payment_status' => $order['payment_status'],
                    'invoice_pdf_path' => $order['invoice_pdf_path']
                ];
            }
            
            return [
                'payment_status' => 'pending',
                'invoice_pdf_path' => null
            ];
        } catch (\PDOException $e) {
            return [
                'payment_status' => 'pending',
                'invoice_pdf_path' => null
            ];
        }
    }

    public function processOrderWithFakturoid(): bool
    {
        if (!isset($_SESSION['user_info']) || !isset($_SESSION['cart'])) {
            header('location: ../cart.php');
            exit();
        }

        if (isset($_SESSION['order_submitted']) && $_SESSION['order_submitted'] === true) {
            header("location: src/Services/FakturoidService.php");
            exit();
        }

        $oauthService = new OAuthService();
        $configService = new ConfigurationService();
        $cartService = new CartService();
        
        $fakturoidConfig = $configService->getConfig('fakturoid');
        $tokenData = $oauthService->refreshToken('fakturoid', $fakturoidConfig);
        
        if (!$tokenData) {
            throw new Exception('Failed to refresh OAuth token');
        }

        $oauthService->saveTokens(
            'fakturoid',
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? '',
            $tokenData['expires_in'] ?? 3600
        );
        
        $aggregatedCart = $cartService->aggregateCart();
        $orderNumber = $this->generateNextOrderNumber();
        
        if (!$this->validateOrderNumber($orderNumber)) {
            throw new Exception("Invalid order number: $orderNumber");
        }
        
        $_SESSION['user_info']['order_number'] = $orderNumber;
        $_SESSION['order_number'] = $orderNumber;

        $this->db->beginTransaction();
        
        try {
            $totalPrice = $this->processCartToOrder($aggregatedCart, $orderNumber);

            $sql = "INSERT INTO orders_user (order_number, name, surname, email, phone, street, house_number, city, zipcode, country, shipping_method, payment_method, branch, branch_name, ico, dic, company_name, price, currency, terms, newsletter, payment_status, order_status, timestamp) VALUES (:order_number, :name, :surname, :email, :phone, :street, :house_number, :city, :zipcode, :country, :shipping_method, :payment_method, :branch, :branch_name, :ico, :dic, :company_name, :price, :currency, :terms, :newsletter, :payment_status, :order_status, :timestamp)";
            $stmt = $this->db->prepare($sql);

            $executeResult = $stmt->execute([
                'order_number' => $orderNumber,
                'name' => $_SESSION['user_info']['name'] ?? '',
                'surname' => $_SESSION['user_info']['surname'] ?? '',
                'email' => $_SESSION['user_info']['email'] ?? '',
                'phone' => $_SESSION['user_info']['phone'] ?? '',
                'street' => $_SESSION['user_info']['street'] ?? '',
                'house_number' => $_SESSION['user_info']['housenumber'] ?? '',
                'city' => $_SESSION['user_info']['city'] ?? '',
                'zipcode' => $_SESSION['user_info']['zipcode'] ?? '',
                'country' => $_SESSION['user_info']['country'] ?? '',
                'shipping_method' => $_SESSION['user_info']['shipping_method'] ?? '',
                'payment_method' => $_SESSION['user_info']['payment_method'] ?? '',
                'branch' => $_SESSION['user_info']['zasilkovna_branch'] ?? '',
                'branch_name' => $_SESSION['user_info']['zasilkovna_name'] ?? '',
                'ico' => $_SESSION['user_info']['ico'] ?? '',
                'dic' => $_SESSION['user_info']['dic'] ?? '',
                'company_name' => $_SESSION['user_info']['companyname'] ?? '',
                'price' => $totalPrice,
                'currency' => 'CZK',
                'newsletter' => $_SESSION['user_info']['newsletter'] ?? 0,
                'terms' => $_SESSION['user_info']['terms'] ?? 0,
                'payment_status' => 'no',
                'order_status' => 'waiting',
                'timestamp' => $_SESSION['user_info']['timestamp'] ?? date('Y-m-d H:i:s')
            ]);

            if ($executeResult) {
                $this->db->commit();
                $_SESSION['order_submitted'] = true;
                $_SESSION['order_processed_at'] = time();
                return true;
            } else {
                throw new Exception("Failed to save order - execute returned false");
            }
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

}