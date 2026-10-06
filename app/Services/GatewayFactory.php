<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\Payment\CashGateway;
use App\Gateways\Payment\MpesaGateway;
use App\Gateways\Payment\StripeGateway;
use App\Gateways\Payment\SelcomGateway;
use App\Gateways\Payment\TigoGateway;
use InvalidArgumentException;

/**
 * Gateway Factory — resolves payment gateway adapters via .env configuration.
 *
 * Configured via PAYMENT_GATEWAY_DEFAULT in .env.
 * Add new gateways by registering them in the $gateways map.
 */
class GatewayFactory
{
    /**
     * Map of gateway names to their implementation classes.
     */
    protected array $gateways = [
        'cash'   => CashGateway::class,
        'mpesa'  => MpesaGateway::class,
        'stripe' => StripeGateway::class,
        'selcom' => SelcomGateway::class,
        'tigo'   => TigoGateway::class,
        'mock'   => CashGateway::class, // Mock uses CashGateway behavior (always succeeds)
    ];

    /**
     * Create a gateway instance by name.
     */
    public function make(?string $gateway = null): PaymentGatewayInterface
    {
        $gateway = $gateway ?? config('services.payment.default', env('PAYMENT_GATEWAY_DEFAULT', 'cash'));

        if (!isset($this->gateways[$gateway])) {
            throw new InvalidArgumentException("Unknown payment gateway: [{$gateway}]. Registered: " . implode(', ', array_keys($this->gateways)));
        }

        $instance = app($this->gateways[$gateway]);

        // Auto-initialize with .env config if available
        $config = $this->resolveConfig($gateway);
        $instance->initialize($config);

        return $instance;
    }

    /**
     * Resolve .env-driven configuration for a specific gateway.
     */
    protected function resolveConfig(string $gateway): array
    {
        return match ($gateway) {
            'mpesa' => [
                'api_key'      => env('MPESA_API_KEY', ''),
                'api_secret'   => env('MPESA_API_SECRET', ''),
                'base_url'     => env('MPESA_BASE_URL', ''),
                'callback_url' => env('MPESA_CALLBACK_URL', ''),
            ],
            'stripe' => [
                'secret_key'  => env('STRIPE_SECRET_KEY', ''),
                'public_key'  => env('STRIPE_PUBLIC_KEY', ''),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
            ],
            'selcom' => [
                'api_key'    => env('SELCOM_API_KEY', ''),
                'api_secret' => env('SELCOM_API_SECRET', ''),
                'base_url'   => env('SELCOM_BASE_URL', ''),
            ],
            'tigo' => [
                'api_key'      => env('TIGO_API_KEY', ''),
                'callback_url' => env('TIGO_CALLBACK_URL', ''),
            ],
            default => [],
        };
    }

    /**
     * Register a new gateway at runtime.
     */
    public function register(string $name, string $class): void
    {
        $this->gateways[$name] = $class;
    }

    /**
     * Get all registered gateway names.
     */
    public function availableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
