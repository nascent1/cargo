<?php

namespace App\Gateways\Payment;

use App\Contracts\PaymentGatewayInterface;

/**
 * Selcom Mobile Money Gateway Stub
 *
 * Integration-ready stub for Selcom Tanzania (mobile money aggregator).
 * When Selcom API credentials are provided via .env, complete the implementation.
 */
class SelcomGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        $baseUrl = config('services.selcom.base_url', env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'));
        $apiKey = config('services.selcom.api_key', env('SELCOM_API_KEY'));
        $apiSecret = config('services.selcom.api_secret', env('SELCOM_API_SECRET'));
        $vendor = config('services.selcom.vendor', env('SELCOM_VENDOR'));

        if (!$apiKey || !$apiSecret || !$vendor) {
            return [
                'success'        => false,
                'transaction_id' => null,
                'fee'            => 0,
                'message'        => 'Selcom gateway not yet configured. Provide SELCOM_API_KEY and SELCOM_API_SECRET in .env.',
                'raw'            => ['gateway' => 'selcom', 'status' => 'not_configured'],
            ];
        }

        $orderId = $metadata['order_id'] ?? uniqid('ORD-');
        
        $body = [
            'vendor'      => $vendor,
            'order_id'    => $orderId,
            'buyer_email' => $metadata['buyer_email'] ?? 'test@example.com',
            'buyer_name'  => $metadata['buyer_name'] ?? 'Guest',
            'buyer_phone' => $metadata['buyer_phone'] ?? '0700000000',
            'amount'      => $amount,
            'currency'    => $currency,
            'no_of_items' => $metadata['no_of_items'] ?? 1,
            'webhook'     => url('/api/v1/webhooks/selcom/callback'),
        ];

        $timestamp = date('c');
        $signedFields = implode(',', array_keys($body));
        $dataToSign = "timestamp={$timestamp}&" . http_build_query($body);
        $digest = base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true));

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'SELCOM ' . base64_encode($apiKey),
                'Digest'        => "SHA-256={$digest}",
                'Timestamp'     => $timestamp,
                'Signed-Fields' => $signedFields,
            ])->post("{$baseUrl}/checkout/create-order-minimal", $body);

            if ($response->successful() && $response->json('result') === 'SUCCESS') {
                return [
                    'success'        => true,
                    'transaction_id' => $response->json('data.reference'),
                    'order_id'       => $orderId,
                    'message'        => 'Payment initiated successfully.',
                    'raw'            => $response->json(),
                ];
            }

            return [
                'success'        => false,
                'transaction_id' => null,
                'message'        => $response->json('message') ?? 'Failed to initiate Selcom payment.',
                'raw'            => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success'        => false,
                'transaction_id' => null,
                'message'        => $e->getMessage(),
                'raw'            => [],
            ];
        }
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        $baseUrl = config('services.selcom.base_url', env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'));
        $apiKey = config('services.selcom.api_key', env('SELCOM_API_KEY'));
        $apiSecret = config('services.selcom.api_secret', env('SELCOM_API_SECRET'));

        if (!$apiKey || !$apiSecret) {
            return [
                'success'        => false,
                'transaction_id' => $transactionId,
                'message'        => 'Selcom gateway not configured. Cannot process refund.',
                'raw'            => ['gateway' => 'selcom', 'status' => 'not_configured'],
            ];
        }

        $body = [
            'order_id' => $transactionId,
        ];

        $timestamp = date('c');
        $signedFields = implode(',', array_keys($body));
        $dataToSign = "timestamp={$timestamp}&" . http_build_query($body);
        $digest = base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true));

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'SELCOM ' . base64_encode($apiKey),
                'Digest'        => "SHA-256={$digest}",
                'Timestamp'     => $timestamp,
                'Signed-Fields' => $signedFields,
            ])->delete("{$baseUrl}/checkout/cancel-order", $body);

            if ($response->successful() && $response->json('result') === 'SUCCESS') {
                return [
                    'success'        => true,
                    'transaction_id' => $transactionId,
                    'message'        => 'Refund processed successfully.',
                    'raw'            => $response->json(),
                ];
            }

            return [
                'success'        => false,
                'transaction_id' => $transactionId,
                'message'        => $response->json('message') ?? 'Selcom refund request failed.',
                'raw'            => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success'        => false,
                'transaction_id' => $transactionId,
                'message'        => $e->getMessage(),
                'raw'            => [],
            ];
        }
    }

    public function verifyWebhook(array $payload, string $signature = ''): bool
    {
        $apiSecret = config('services.selcom.api_secret', env('SELCOM_API_SECRET'));
        if (!$apiSecret || empty($signature)) {
            return false;
        }

        // Selcom sends HMAC-SHA256 digest of the payload body
        // The signature header typically contains "SHA-256=<base64-encoded-hmac>"
        $signatureValue = $signature;
        if (str_starts_with($signature, 'SHA-256=')) {
            $signatureValue = substr($signature, 8);
        }

        // Reconstruct the digest from the payload
        $dataToSign = http_build_query($payload);
        $expectedDigest = base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true));

        return hash_equals($expectedDigest, $signatureValue);
    }

    public function checkStatus(string $orderId): array
    {
        $baseUrl = config('services.selcom.base_url', env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'));
        $apiKey = config('services.selcom.api_key', env('SELCOM_API_KEY'));
        $apiSecret = config('services.selcom.api_secret', env('SELCOM_API_SECRET'));
        $vendor = config('services.selcom.vendor', env('SELCOM_VENDOR'));

        if (!$apiKey) return ['success' => false, 'message' => 'Not configured'];

        $timestamp = date('c');
        $query = "order_id={$orderId}";
        $dataToSign = "timestamp={$timestamp}&{$query}";
        $digest = base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true));

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'SELCOM ' . base64_encode($apiKey),
                'Digest'        => "SHA-256={$digest}",
                'Timestamp'     => $timestamp,
            ])->get("{$baseUrl}/checkout/order-status?{$query}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status'  => $response->json('data.payment_status'), // e.g. COMPLETED, PENDING
                    'raw'     => $response->json()
                ];
            }
            return ['success' => false, 'message' => 'Status check failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
