<?php

namespace App\Services\Refunds;

/**
 * Factory for resolving the appropriate refund gateway based on payment method.
 */
class RefundGatewayFactory
{
    /**
     * Resolve a refund gateway for the given payment method slug.
     */
    public static function make(string $paymentMethodSlug): RefundGatewayInterface
    {
        return match ($paymentMethodSlug) {
            'selcom', 'mobile-money', 'mpesa', 'tigopesa', 'airtel-money'
                => new SelcomRefundGateway(),
            default
                => new ManualRefundGateway(),
        };
    }

    /**
     * Resolve a refund gateway from a booking's payment channel.
     */
    public static function fromBooking(\App\Models\Booking $booking): RefundGatewayInterface
    {
        $channel = $booking->payment_channel ?? 'cash';
        return static::make(strtolower($channel));
    }
}
