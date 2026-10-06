<?php

namespace App\Gateways\Payment;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class CashGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        $receiptNo = 'CASH-' . now()->format('YmdHis') . '-' . rand(100, 999);
        Log::info("Recorded Cash/COD payment of {$currency} {$amount}. Receipt: {$receiptNo}");

        return [
            'success' => true,
            'transaction_id' => $receiptNo,
            'fee' => 0.0,
            'message' => 'Cash payment logged successfully.',
            'raw' => ['receipt' => $receiptNo, 'cashier' => $metadata['cashier_id'] ?? auth()->id()],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        $refundReceipt = 'CASH-REF-' . now()->format('YmdHis');
        return [
            'success' => true,
            'transaction_id' => $refundReceipt,
            'fee' => 0.0,
            'message' => 'Cash refund issued.',
            'raw' => ['original_receipt' => $transactionId, 'reason' => $reason],
        ];
    }

    public function verifyWebhook(array $payload, string $signature = ''): bool
    {
        return true;
    }
}
