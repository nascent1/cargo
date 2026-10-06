<?php

namespace App\Services\Refunds;

/**
 * Manual/Cash refund gateway — always marks as pending manual processing.
 * Used for bookings paid via cash, POS, or any non-electronic method.
 */
class ManualRefundGateway implements RefundGatewayInterface
{
    public function refund(string $paymentReference, float $amount, string $currency, string $reason = ''): array
    {
        return [
            'success' => true,
            'reference' => 'MANUAL-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'message' => 'Refund queued for manual processing. Cashier will disburse.',
            'gateway_response' => [
                'type' => 'manual',
                'amount' => $amount,
                'currency' => $currency,
            ],
        ];
    }

    public function checkRefundStatus(string $refundReference): array
    {
        return [
            'status' => 'pending_manual',
            'message' => 'This refund requires manual disbursement.',
            'gateway_response' => [],
        ];
    }

    public function supportsAutomaticRefund(): bool
    {
        return false;
    }
}
