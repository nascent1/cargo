<?php

namespace App\Services\Refunds;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Selcom payment gateway refund implementation.
 *
 * Uses Selcom's refund API to reverse mobile money payments.
 * Falls back to manual processing if the API call fails.
 */
class SelcomRefundGateway implements RefundGatewayInterface
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.selcom.api_key', env('SELCOM_API_KEY', ''));
        $this->apiSecret = config('services.selcom.api_secret', env('SELCOM_API_SECRET', ''));
        $this->baseUrl = config('services.selcom.base_url', env('SELCOM_BASE_URL', ''));
    }

    public function refund(string $paymentReference, float $amount, string $currency, string $reason = ''): array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning("Selcom refund skipped: API not configured", [
                'payment_reference' => $paymentReference,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'reference' => null,
                'message' => 'Selcom API not configured. Refund requires manual processing.',
                'gateway_response' => ['error' => 'gateway_not_configured'],
            ];
        }

        try {
            $refundRef = 'SREF-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v1/checkout/cancel-order", [
                'order_id' => $paymentReference,
                'amount' => $amount,
                'reason' => $reason ?: 'Customer cancellation refund',
            ]);

            if ($response->successful() && $response->json('result') === 'SUCCESS') {
                Log::info("Selcom refund initiated successfully", [
                    'payment_reference' => $paymentReference,
                    'refund_reference' => $refundRef,
                    'amount' => $amount,
                ]);

                return [
                    'success' => true,
                    'reference' => $refundRef,
                    'message' => 'Selcom refund initiated successfully.',
                    'gateway_response' => $response->json(),
                ];
            }

            Log::error("Selcom refund failed", [
                'payment_reference' => $paymentReference,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'reference' => $refundRef,
                'message' => 'Selcom refund failed: ' . ($response->json('message') ?? 'Unknown error'),
                'gateway_response' => $response->json() ?? [],
            ];
        } catch (\Exception $e) {
            Log::error("Selcom refund exception", [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reference' => null,
                'message' => 'Selcom refund error: ' . $e->getMessage(),
                'gateway_response' => ['exception' => $e->getMessage()],
            ];
        }
    }

    public function checkRefundStatus(string $refundReference): array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return [
                'status' => 'unknown',
                'message' => 'Selcom API not configured.',
                'gateway_response' => [],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/v1/checkout/order-status", [
                'order_id' => $refundReference,
            ]);

            return [
                'status' => $response->json('payment_status') ?? 'unknown',
                'message' => $response->json('message') ?? '',
                'gateway_response' => $response->json() ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'gateway_response' => [],
            ];
        }
    }

    public function supportsAutomaticRefund(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }
}
