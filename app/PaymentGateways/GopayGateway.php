<?php

namespace App\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\ConfigurationService;
use Exception;

class GopayGateway implements PaymentGatewayInterface
{
    private ConfigurationService $config;
    private $gopay;

    public function __construct(ConfigurationService $config)
    {
        $this->config = $config;
        $this->initializeGopay();
    }

    private function initializeGopay(): void
    {
        $gopayConfig = $this->config->getGopayConfig();
        
        $this->gopay = \GoPay\payments([
            'goid' => $gopayConfig['goid'],
            'clientId' => $gopayConfig['client_id'],
            'clientSecret' => $gopayConfig['client_secret'],
            'isProductionMode' => $gopayConfig['is_production'],
            'language' => $gopayConfig['language'] ?? 'CS'
        ]);
    }

    public function createPayment(array $paymentData): array
    {
        try {
            $payment = [
                'payer' => [
                    'default_payment_instrument' => 'BANK_ACCOUNT',
                    'allowed_payment_instruments' => ['BANK_ACCOUNT', 'PAYMENT_CARD'],
                    'contact' => [
                        'first_name' => $paymentData['first_name'],
                        'last_name' => $paymentData['last_name'],
                        'email' => $paymentData['email'],
                        'phone_number' => $paymentData['phone'] ?? null,
                        'city' => $paymentData['city'] ?? null,
                        'street' => $paymentData['street'] ?? null,
                        'postal_code' => $paymentData['postal_code'] ?? null,
                        'country_code' => $paymentData['country_code'] ?? 'CZE'
                    ]
                ],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'CZK',
                'order_number' => $paymentData['order_number'],
                'order_description' => $paymentData['description'] ?? 'Objednávka',
                'items' => $paymentData['items'] ?? [],
                'return_url' => $paymentData['return_url'],
                'notify_url' => $paymentData['notify_url'],
                'lang' => $paymentData['lang'] ?? 'cs'
            ];

            $response = $this->gopay->createPayment($payment);
            
            if ($response->hasSucceed()) {
                return [
                    'success' => true,
                    'payment_id' => $response->json['id'],
                    'gateway_url' => $response->json['gw_url'],
                    'data' => $response->json
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json['errors'][0]['message'] ?? 'Unknown error',
                    'data' => $response->json
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $status = $this->gopay->getStatus($paymentId);
            
            return [
                'success' => true,
                'status' => $status->json['state'],
                'order_number' => $status->json['order_number'] ?? null,
                'data' => $status->json
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function cancelPayment(string $paymentId): array
    {
        try {
            $response = $this->gopay->voidAuthorization($paymentId);
            
            return [
                'success' => $response->hasSucceed(),
                'data' => $response->json
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function refundPayment(string $paymentId, float $amount): array
    {
        try {
            $refund = [
                'amount' => $amount
            ];
            
            $response = $this->gopay->refundPayment($paymentId, $refund);
            
            return [
                'success' => $response->hasSucceed(),
                'refund_id' => $response->json['id'] ?? null,
                'data' => $response->json
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function processWebhook(array $webhookData): array
    {
        try {
            $paymentId = $webhookData['id'] ?? null;
            
            if (!$paymentId) {
                throw new Exception('Invalid webhook data - missing payment ID');
            }

            $statusResult = $this->getPaymentStatus($paymentId);
            
            if (!$statusResult['success']) {
                throw new Exception('Failed to get payment status');
            }

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'status' => $statusResult['status'],
                'order_number' => $statusResult['order_number']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function isWebhookSignatureValid(array $headers, string $payload): bool
    {
        return true;
    }
}