<?php

namespace App\Gateways\Payment\Push;

use App\Models\CargoPayment;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Vodacom M-Pesa Tanzania — M-Pesa OpenAPI (openapiportal.m-pesa.com).
 *
 * 1. getSession: API key RSA-encrypted with the OpenAPI public key → session id (cached).
 * 2. c2bPayment/singleStage: session id RSA-encrypted as bearer. The call is SYNCHRONOUS —
 *    it returns once the customer enters their PIN (INS-0) or the prompt times out.
 *
 * Credentials: api_key, merchant_id (service provider / short code), extra.public_key.
 */
class VodacomMpesaGateway extends AbstractPushGateway
{
    private const MARKET = 'vodacomTZN';

    private const RESULT_MESSAGES = [
        'INS-5'    => 'The customer cancelled the payment.',
        'INS-6'    => 'The transaction failed at M-Pesa.',
        'INS-9'    => 'The customer did not respond to the payment prompt in time.',
        'INS-10'   => 'Duplicate transaction reference.',
        'INS-2001' => 'Wrong M-Pesa PIN entered.',
        'INS-2006' => 'Insufficient M-Pesa balance.',
        'INS-2051' => 'The phone number is not registered for M-Pesa.',
    ];

    public function initiate(CargoPayment $payment): PushResult
    {
        $consignmentNumber = $payment->consignment?->consignment_number;

        $body = [
            'input_Amount'                   => (string) $this->amount($payment),
            'input_Country'                  => 'TZN',
            'input_Currency'                 => 'TZS',
            'input_CustomerMSISDN'           => $payment->payer_msisdn,
            'input_ServiceProviderCode'      => (string) $this->config->merchant_id,
            'input_ThirdPartyConversationID' => $payment->reference,
            'input_TransactionReference'     => $payment->reference, // 1–20 chars
            'input_PurchasedItemsDesc'       => 'Cargo ' . $consignmentNumber,
        ];

        // Synchronous: allow time for the customer to enter their PIN
        $response = $this->withSession($payment, fn (string $bearer) => $this->call(
            $payment, 'push', 'POST',
            $this->baseUrl() . '/ipg/v2/' . self::MARKET . '/c2bPayment/singleStage/',
            $body,
            ['Authorization' => 'Bearer ' . $bearer, 'Origin' => '*'],
            150,
        ));

        $code = $response->json('output_ResponseCode');
        $conversationId = $response->json('output_ConversationID');

        if ($code === 'INS-0') {
            return PushResult::paid($response->json('output_TransactionID'), $conversationId, (float) $payment->amount, $response->json() ?? []);
        }

        return PushResult::failed(
            self::RESULT_MESSAGES[$code] ?? ($response->json('output_ResponseDesc') ?: 'M-Pesa request failed (HTTP ' . $response->status() . ').'),
            $conversationId,
            $response->json() ?? [],
        );
    }

    public function supportsStatusQuery(): bool
    {
        return true;
    }

    public function queryStatus(CargoPayment $payment): PushResult
    {
        $response = $this->withSession($payment, fn (string $bearer) => $this->call(
            $payment, 'status', 'GET',
            $this->baseUrl() . '/ipg/v2/' . self::MARKET . '/queryTransactionStatus/',
            [
                'input_QueryReference'           => $payment->provider_request_id ?: $payment->reference,
                'input_ServiceProviderCode'      => (string) $this->config->merchant_id,
                'input_ThirdPartyConversationID' => $payment->reference . 'Q' . now()->format('His'),
                'input_Country'                  => 'TZN',
            ],
            ['Authorization' => 'Bearer ' . $bearer, 'Origin' => '*'],
        ));

        $status = strtolower((string) $response->json('output_ResponseTransactionStatus'));

        return match (true) {
            $status === 'completed' => PushResult::paid($response->json('output_TransactionID') ?? $payment->provider_reference, $payment->provider_request_id, null, $response->json() ?? []),
            in_array($status, ['failed', 'cancelled', 'expired', 'declined'], true) => PushResult::failed('M-Pesa reports the transaction as ' . $status . '.', null, $response->json() ?? []),
            default => PushResult::pending(null, null, $response->json() ?? []),
        };
    }

    /**
     * Run a request with a cached, encrypted session bearer; on 401 refresh the session once.
     */
    private function withSession(CargoPayment $payment, callable $request)
    {
        $response = $request($this->encrypt($this->sessionId($payment)));

        if ($response->status() === 401) {
            Cache::forget($this->sessionCacheKey());
            $response = $request($this->encrypt($this->sessionId($payment)));
        }

        return $response;
    }

    private function sessionId(CargoPayment $payment): string
    {
        return Cache::remember($this->sessionCacheKey(), now()->addMinutes(50), function () use ($payment) {
            $response = $this->call(
                $payment, 'token', 'GET',
                $this->baseUrl() . '/ipg/v2/' . self::MARKET . '/getSession/',
                [],
                ['Authorization' => 'Bearer ' . $this->encrypt((string) $this->config->api_key), 'Origin' => '*'],
            );

            $sessionId = $response->json('output_SessionID');
            if ($response->json('output_ResponseCode') !== 'INS-0' || !$sessionId) {
                throw new RuntimeException('M-Pesa session request failed: ' . ($response->json('output_ResponseDesc') ?? 'HTTP ' . $response->status()));
            }

            return $sessionId;
        });
    }

    private function sessionCacheKey(): string
    {
        return 'cargo_pay:mpesa_session:' . md5($this->config->environment . '|' . $this->config->api_key);
    }

    /**
     * RSA (PKCS#1 v1.5) encrypt with the OpenAPI public key, base64-encoded.
     */
    private function encrypt(string $value): string
    {
        $key = trim((string) $this->config->extra('public_key'));
        if (!str_contains($key, 'BEGIN PUBLIC KEY')) {
            $key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(preg_replace('/\s+/', '', $key), 64, "\n") . "-----END PUBLIC KEY-----";
        }

        if (!openssl_public_encrypt($value, $encrypted, $key, OPENSSL_PKCS1_PADDING)) {
            throw new RuntimeException('Could not encrypt with the M-Pesa public key — check the key in Payment Channels.');
        }

        return base64_encode($encrypted);
    }
}
