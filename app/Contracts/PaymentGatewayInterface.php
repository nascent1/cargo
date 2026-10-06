<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Initialize gateway driver with dynamic JSON config.
     */
    public function initialize(array $config): self;

    /**
     * Process a charge/payment.
     *
     * @return array ['success' => bool, 'transaction_id' => string, 'fee' => float, 'message' => string, 'raw' => array]
     */
    public function charge(float $amount, string $currency, array $metadata = []): array;

    /**
     * Process a refund or reversal.
     */
    public function refund(string $transactionId, float $amount, string $reason = ''): array;

    /**
     * Verify incoming webhook notification payload and signature.
     */
    public function verifyWebhook(array $payload, string $signature = ''): bool;
}
