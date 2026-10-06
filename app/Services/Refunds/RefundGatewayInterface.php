<?php

namespace App\Services\Refunds;

interface RefundGatewayInterface
{
    /**
     * Initiate a refund through the payment gateway.
     *
     * @param string $paymentReference  The original payment reference/order ID
     * @param float  $amount           The amount to refund
     * @param string $currency         Currency code (TZS, USD)
     * @param string $reason           Reason for the refund
     * @return array{success: bool, reference: string|null, message: string, gateway_response: array}
     */
    public function refund(string $paymentReference, float $amount, string $currency, string $reason = ''): array;

    /**
     * Check the status of a previously initiated refund.
     *
     * @param string $refundReference  The refund reference returned by refund()
     * @return array{status: string, message: string, gateway_response: array}
     */
    public function checkRefundStatus(string $refundReference): array;

    /**
     * Whether this gateway supports automatic refunds.
     */
    public function supportsAutomaticRefund(): bool;
}
