<?php

namespace App\Interfaces;

interface EmailServiceInterface
{
    public function sendEmail(string $to, string $subject, string $body, ?string $attachmentPath = null): array;
    public function sendPasswordReset(string $to, string $resetLink): bool;
    public function sendRegistrationConfirmation(string $to, string $confirmationLink): bool;
    public function sendOrderConfirmation(string $to, array $orderData): bool;
    public function validateEmailAddress(string $email): bool;
}