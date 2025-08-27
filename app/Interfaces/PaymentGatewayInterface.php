<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    public function createPayment(array $paymentData): array;
    public function getPaymentStatus(string $paymentId): array;
    public function cancelPayment(string $paymentId): array;
    public function refundPayment(string $paymentId, float $amount): array;
    public function processWebhook(array $webhookData): array;
    public function isWebhookSignatureValid(array $headers, string $payload): bool;
}