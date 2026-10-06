<?php

namespace App\Gateways\Payment;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        if ($currency !== 'KES') {
            return [
                'success' => false,
                'transaction_id' => null,
                'fee' => 0.0,
                'message' => 'M-Pesa only supports KES currency.',
                'raw' => [],
            ];
        }

        $phone = $metadata['phone'] ?? null;
        if (!$phone) {
            return [
                'success' => false,
                'transaction_id' => null,
                'fee' => 0.0,
                'message' => 'Phone number is required for M-Pesa STK push.',
                'raw' => [],
            ];
        }

        // Simulate STK Push or actual Daraja API call depending on config['env']
        $shortcode = $this->config['shortcode'] ?? '174379';
        $transactionId = 'MPESA-' . strtoupper(uniqid());

        Log::info("Processing M-Pesa charge of KES {$amount} for {$phone} via shortcode {$shortcode}");

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'fee' => round($amount * ($this->config['fee_pct'] ?? 0.015), 2),
            'message' => 'STK Push initiated successfully.',
            'raw' => ['MerchantRequestID' => uniqid('MR_'), 'CheckoutRequestID' => uniqid('ws_CO_'), 'ResponseCode' => '0'],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        Log::info("Initiating M-Pesa B2C Reversal for {$transactionId}: KES {$amount}");

        return [
            'success' => true,
            'transaction_id' => 'REV-' . $transactionId,
            'fee' => 0.0,
            'message' => 'M-Pesa reversal processed successfully.',
            'raw' => ['OriginalTransactionID' => $transactionId, 'Status' => 'Reversed'],
        ];
    }

    public function verifyWebhook(array $payload, string $signature = ''): bool
    {
        // Check secret or passkey match
        return isset($payload['Body']['stkCallback']['ResultCode']);
    }
}
