<?php

namespace App\Gateways\Payment;

use App\Contracts\PaymentGatewayInterface;

/**
 * Tigo Pesa Mobile Money Gateway Stub
 *
 * Integration-ready stub for Tigo Pesa (Tanzania mobile money).
 * When Tigo API credentials are provided via .env, complete the implementation.
 */
class TigoGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        // TODO: Implement Tigo Pesa USSD Push API
        // POST {base_url}/tigo/payment-auth/authorize
        // Body: MasterMerchantId, SubscriberMsisdn, Amount, Language, TerminalId, OriginPayment

        return [
            'success'        => false,
            'transaction_id' => null,
            'fee'            => 0,
            'message'        => 'Tigo Pesa gateway not yet configured. Provide TIGO_API_KEY in .env.',
            'raw'            => ['gateway' => 'tigo', 'status' => 'not_configured'],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        return [
            'success'        => false,
            'transaction_id' => $transactionId,
            'message'        => 'Tigo refund not yet implemented.',
            'raw'            => [],
        ];
    }

    public function verifyWebhook(array $payload, string $signature = ''): bool
    {
        // TODO: Verify callback IP whitelist and/or signature
        return false;
    }
}
