<?php

namespace Tests\Unit\Gateways;

use App\Gateways\Payment\SelcomGateway;
use Tests\TestCase;

class SelcomGatewayTest extends TestCase
{
    protected SelcomGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new SelcomGateway();
    }

    /** @test */
    public function it_rejects_webhook_with_empty_signature(): void
    {
        config(['services.selcom.api_secret' => 'test-secret-key']);

        $payload = ['order_id' => 'ORD-123', 'payment_status' => 'COMPLETED'];

        // BUG-012: Empty signature must be rejected (was previously accepted)
        $this->assertFalse($this->gateway->verifyWebhook($payload, ''));
    }

    /** @test */
    public function it_rejects_webhook_with_no_api_secret_configured(): void
    {
        config(['services.selcom.api_secret' => null]);

        $payload = ['order_id' => 'ORD-123', 'payment_status' => 'COMPLETED'];

        $this->assertFalse($this->gateway->verifyWebhook($payload, 'some-signature'));
    }

    /** @test */
    public function it_rejects_webhook_with_forged_signature(): void
    {
        config(['services.selcom.api_secret' => 'real-secret-key']);

        $payload = ['order_id' => 'ORD-123', 'payment_status' => 'COMPLETED'];

        // BUG-012: A random non-empty signature must NOT pass verification
        $this->assertFalse($this->gateway->verifyWebhook($payload, 'totally-fake-signature'));
    }

    /** @test */
    public function it_accepts_webhook_with_valid_hmac_signature(): void
    {
        $secret = 'test-secret-key-12345';
        config(['services.selcom.api_secret' => $secret]);

        $payload = ['order_id' => 'ORD-456', 'payment_status' => 'COMPLETED', 'amount' => '35000'];

        // Generate a valid signature the same way the gateway does
        $dataToSign = http_build_query($payload);
        $validDigest = base64_encode(hash_hmac('sha256', $dataToSign, $secret, true));

        $this->assertTrue($this->gateway->verifyWebhook($payload, $validDigest));
    }

    /** @test */
    public function it_accepts_webhook_with_sha256_prefixed_signature(): void
    {
        $secret = 'test-secret-key-12345';
        config(['services.selcom.api_secret' => $secret]);

        $payload = ['order_id' => 'ORD-789', 'payment_status' => 'COMPLETED'];

        $dataToSign = http_build_query($payload);
        $validDigest = base64_encode(hash_hmac('sha256', $dataToSign, $secret, true));

        // Selcom may prefix with "SHA-256="
        $this->assertTrue($this->gateway->verifyWebhook($payload, "SHA-256={$validDigest}"));
    }

    /** @test */
    public function it_rejects_webhook_when_payload_is_tampered(): void
    {
        $secret = 'test-secret-key-12345';
        config(['services.selcom.api_secret' => $secret]);

        $originalPayload = ['order_id' => 'ORD-456', 'payment_status' => 'COMPLETED', 'amount' => '35000'];

        // Sign with original payload
        $dataToSign = http_build_query($originalPayload);
        $validDigest = base64_encode(hash_hmac('sha256', $dataToSign, $secret, true));

        // Tamper the payload (change amount)
        $tamperedPayload = ['order_id' => 'ORD-456', 'payment_status' => 'COMPLETED', 'amount' => '1'];

        // Signature from original payload should NOT verify against tampered payload
        $this->assertFalse($this->gateway->verifyWebhook($tamperedPayload, $validDigest));
    }

    /** @test */
    public function it_returns_not_configured_when_credentials_missing(): void
    {
        config(['services.selcom.api_key' => null]);
        config(['services.selcom.api_secret' => null]);
        config(['services.selcom.vendor' => null]);

        // Clear env values too
        putenv('SELCOM_API_KEY');
        putenv('SELCOM_API_SECRET');
        putenv('SELCOM_VENDOR');

        $result = $this->gateway->charge(35000, 'TZS', []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not yet configured', $result['message']);
    }

    /** @test */
    public function refund_returns_not_configured_when_credentials_missing(): void
    {
        config(['services.selcom.api_key' => null]);
        config(['services.selcom.api_secret' => null]);
        putenv('SELCOM_API_KEY');
        putenv('SELCOM_API_SECRET');

        $result = $this->gateway->refund('TX-123', 35000, 'Customer request');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not configured', $result['message']);
    }
}
