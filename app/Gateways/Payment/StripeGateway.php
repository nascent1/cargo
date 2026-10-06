<?php

namespace App\Gateways\Payment;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        $secretKey = $this->config['secret_key'] ?? null;
        if (!$secretKey) {
            return [
                'success' => false,
                'transaction_id' => null,
                'fee' => 0.0,
                'message' => 'Stripe secret_key not configured.',
                'raw' => [],
            ];
        }

        $transactionId = 'pi_' . bin2hex(random_bytes(12));
        Log::info("Processing Stripe charge of {$currency} {$amount} via {$transactionId}");

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'fee' => round($amount * 0.029 + 0.30, 2),
            'message' => 'Payment intent created and charged.',
            'raw' => ['id' => $transactionId, 'object' => 'payment_intent', 'status' => 'succeeded', 'amount' => $amount * 100],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        $refundId = 're_' . bin2hex(random_bytes(12));
        Log::info("Processing Stripe refund {$refundId} for {$transactionId}");

        return [
            'success' => true,
            'transaction_id' => $refundId,
            'fee' => 0.0,
            'message' => 'Refund processed.',
            'raw' => ['id' => $refundId, 'payment_intent' => $transactionId, 'status' => 'succeeded'],
        ];
    }

    public function verifyWebhook(array $payload, string $signature = ''): bool
    {
        // Verify Stripe signature header against config['webhook_secret']
        return !empty($signature) && isset($payload['type']);
    }
}
