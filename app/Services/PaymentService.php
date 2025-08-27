<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use PDO;
use Exception;

class PaymentService
{
    private PDO $db;
    private OrderService $orderService;
    private PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway, OrderService $orderService)
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->paymentGateway = $paymentGateway;
        $this->orderService = $orderService;
    }

    public function processPaymentNotification(array $data): array
    {
        if (!$data || !isset($data['id'])) {
            throw new Exception('Invalid request data', 400);
        }

        $paymentId = $data['id'];

        try {
            $status = $this->getPaymentStatus($paymentId);
            
            if (!$status || !isset($status['state'])) {
                throw new Exception('Failed to get payment status', 400);
            }
            
            $paymentState = $status['state'];
            $orderNumber = $status['order_number'] ?? null;
            
            if (!$orderNumber) {
                throw new Exception('Order number not found in payment data', 400);
            }
            
            $existingOrder = $this->orderService->getOrderByNumber($orderNumber);
            if (!$existingOrder) {
                throw new Exception('Order not found in database', 400);
            }
            
            $result = $this->updatePaymentStatus($orderNumber, $paymentState);
            
            return [
                'success' => true,
                'order_number' => $orderNumber,
                'payment_state' => $paymentState,
                'status_code' => 200
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updatePaymentStatus(string $orderNumber, string $paymentState): bool
    {
        try {
            $this->db->beginTransaction();
            
            switch ($paymentState) {
                case 'PAID':
                    $stmt = $this->db->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
                    $stmt->execute([':status' => 'paid', ':order' => $orderNumber]);
                    
                    $this->updateInventoryAfterPaidOrder($orderNumber);
                    break;
                    
                case 'CANCELED':
                    $stmt = $this->db->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
                    $stmt->execute([':status' => 'canceled', ':order' => $orderNumber]);
                    break;
                    
                case 'TIMEOUTED':
                    $stmt = $this->db->prepare("UPDATE orders_user SET payment_status = :status WHERE order_number = :order");
                    $stmt->execute([':status' => 'timeouted', ':order' => $orderNumber]);
                    break;
                    
                case 'CREATED':
                case 'PAYMENT_METHOD_CHOSEN':
                    break;
                    
                default:
                    error_log("Unknown payment state: $paymentState for order: $orderNumber");
                    break;
            }
            
            $this->db->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception('Database error: ' . $e->getMessage(), 500);
        }
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $result = $this->paymentGateway->getPaymentStatus($paymentId);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to get payment status');
            }
            
            return [
                'id' => $paymentId,
                'state' => $result['status'],
                'order_number' => $result['order_number']
            ];
            
        } catch (Exception $e) {
            error_log("Payment status check failed: " . $e->getMessage());
            throw new Exception('Failed to get payment status: ' . $e->getMessage(), 400);
        }
    }

    public function processWebhook(array $webhookData, array $headers = [], string $payload = ''): array
    {
        try {
            if (!empty($headers) && !empty($payload)) {
                if (!$this->paymentGateway->isWebhookSignatureValid($headers, $payload)) {
                    throw new Exception('Invalid webhook signature');
                }
            }

            return $this->paymentGateway->processWebhook($webhookData);
        } catch (Exception $e) {
            error_log("Webhook processing failed: " . $e->getMessage());
            throw new Exception('Failed to process webhook: ' . $e->getMessage(), 400);
        }
    }

    private function updateInventoryAfterPaidOrder(string $orderNumber): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT oi.item_id, oi.quantity, oi.variant_id 
                FROM order_items oi
                INNER JOIN orders_user ou ON oi.order_id = ou.id
                WHERE ou.order_number = :order_number
            ");
            $stmt->execute([':order_number' => $orderNumber]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orderItems as $item) {
                if ($item['variant_id']) {
                    $stmt = $this->db->prepare("
                        UPDATE product_variants 
                        SET stock_quantity = GREATEST(0, stock_quantity - :quantity)
                        WHERE id = :variant_id
                    ");
                    $stmt->execute([
                        ':quantity' => $item['quantity'],
                        ':variant_id' => $item['variant_id']
                    ]);
                }
            }
            
        } catch (\PDOException $e) {
            throw new Exception('Failed to update inventory: ' . $e->getMessage());
        }
    }

    public function createPayment(array $orderData): array
    {
        try {
           
            return [
                'payment_id' => 'mock_' . uniqid(),
                'gateway_url' => 'https://gate.gopay.cz/gw/v3/mock-payment',
                'state' => 'CREATED'
            ];
            
        } catch (Exception $e) {
            throw new Exception('Failed to create payment: ' . $e->getMessage());
        }
    }

    public function getPaymentMethods(): array
    {
        return [
            'CARD_PAYMENT' => [
                'name' => 'Platební karta',
                'enabled' => true,
                'fee' => 0
            ],
            'BANK_ACCOUNT' => [
                'name' => 'Bankovní převod',
                'enabled' => true,
                'fee' => 0
            ],
            'GOPAY_WALLET' => [
                'name' => 'GoPay peněženka',
                'enabled' => true,
                'fee' => 0
            ]
        ];
    }
}