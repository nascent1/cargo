<?php

namespace App\Gateways\Payment\Push;

use App\Contracts\UssdPushGateway;
use App\Models\CargoPayment;
use App\Models\IntegrationConfig;
use App\Models\SystemConfig;
use RuntimeException;

/**
 * Resolves USSD push drivers from payment_methods.driver + integration_configs credentials.
 *
 * DRIVERS also describes each driver's credential fields, which the admin
 * Payment Channels page renders and isConfigured() checks.
 */
class PushGatewayManager
{
    public const DRIVERS = [
        'vodacom_mpesa' => [
            'label'  => 'Vodacom M-Pesa',
            'class'  => VodacomMpesaGateway::class,
            'fields' => [
                'api_key'          => ['label' => 'API Key', 'secret' => true, 'required' => true],
                'merchant_id'      => ['label' => 'Service Provider Code (short code)', 'required' => true],
                'extra.public_key' => ['label' => 'OpenAPI Public Key', 'type' => 'textarea', 'required' => true],
            ],
        ],
        'airtel_money' => [
            'label'  => 'Airtel Money',
            'class'  => AirtelMoneyGateway::class,
            'fields' => [
                'api_key'    => ['label' => 'Client ID', 'required' => true],
                'api_secret' => ['label' => 'Client Secret', 'secret' => true, 'required' => true],
            ],
        ],
        'tigo_pesa' => [
            'label'  => 'Tigo Pesa',
            'class'  => TigoPesaGateway::class,
            'fields' => [
                'extra.username'    => ['label' => 'API Username', 'required' => true],
                'extra.password'    => ['label' => 'API Password', 'secret' => true, 'required' => true],
                'merchant_id'       => ['label' => 'Biller MSISDN', 'required' => true],
                'extra.biller_code' => ['label' => 'Biller Code (reference prefix)'],
                'extra.token_path'  => ['label' => 'Token Path', 'placeholder' => '/token'],
                'extra.push_path'   => ['label' => 'Push Path', 'placeholder' => '/API/BillerPayments/BillerPay'],
            ],
        ],
        'halopesa' => [
            'label'  => 'Halopesa',
            'class'  => HalopesaGateway::class,
            'fields' => [
                'api_key'          => ['label' => 'Username / Client ID', 'required' => true],
                'api_secret'       => ['label' => 'Password / Secret', 'secret' => true, 'required' => true],
                'merchant_id'      => ['label' => 'Merchant / Business Number', 'required' => true],
                'extra.token_path' => ['label' => 'Token Path', 'required' => true],
                'extra.push_path'  => ['label' => 'Push Path', 'required' => true],
            ],
        ],
        'selcom_pesa' => [
            'label'  => 'Selcom Pesa',
            'class'  => SelcomPesaGateway::class,
            'fields' => [
                'api_key'     => ['label' => 'API Key', 'required' => true],
                'api_secret'  => ['label' => 'API Secret', 'secret' => true, 'required' => true],
                'merchant_id' => ['label' => 'Vendor ID', 'required' => true],
            ],
        ],
    ];

    /** @var array<string, IntegrationConfig|null> */
    private array $integrations = [];

    public function integration(string $driver): ?IntegrationConfig
    {
        if (!array_key_exists($driver, $this->integrations)) {
            $this->integrations[$driver] = IntegrationConfig::where('channel', 'payment')->where('provider', $driver)->first();
        }

        return $this->integrations[$driver];
    }

    /**
     * Credentials present, URL set and integration switched on.
     */
    public function isConfigured(?string $driver): bool
    {
        $config = $driver ? $this->integration($driver) : null;
        if (!$config || !$config->is_active || !$config->activeUrl() || !isset(self::DRIVERS[$driver])) {
            return false;
        }

        foreach (self::DRIVERS[$driver]['fields'] as $field => $meta) {
            if (($meta['required'] ?? false) && blank(static::fieldValue($config, $field))) {
                return false;
            }
        }

        return true;
    }

    /**
     * The simulator may stand in for unconfigured channels only outside production.
     */
    public function simulatorAllowed(): bool
    {
        return app()->environment(['local', 'testing'])
            && SystemConfig::getValue('cargo_payment_simulator_enabled', '0') === '1';
    }

    public function isAvailable(?string $driver): bool
    {
        return isset(self::DRIVERS[$driver]) && ($this->isConfigured($driver) || $this->simulatorAllowed());
    }

    public function make(string $driver, bool $simulated = false): UssdPushGateway
    {
        if ($simulated) {
            if (!$this->simulatorAllowed()) {
                throw new RuntimeException('The payment simulator is disabled in this environment.');
            }

            return new SimulatorGateway($driver);
        }

        if (!$this->isConfigured($driver)) {
            throw new RuntimeException((self::DRIVERS[$driver]['label'] ?? $driver) . ' is not configured. Set it up in Settings › Payment Channels.');
        }

        $class = self::DRIVERS[$driver]['class'];

        return new $class($this->integration($driver), $driver);
    }

    public function forPayment(CargoPayment $payment): UssdPushGateway
    {
        return $this->make($payment->driver, $payment->isSimulated());
    }

    /**
     * Callback URL registered with the provider; carries the integration's secret token.
     */
    public static function callbackUrl(?IntegrationConfig $config, string $driver): string
    {
        $url = $config?->callback_url ?: url('/api/v1/payments/callback/' . $driver);
        $token = $config?->extra('callback_token');

        return $token ? $url . (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($token) : $url;
    }

    /**
     * Read a field key from DRIVERS ('api_key' or 'extra.public_key') off an integration row.
     */
    public static function fieldValue(IntegrationConfig $config, string $field): mixed
    {
        return str_starts_with($field, 'extra.')
            ? $config->extra(substr($field, 6))
            : $config->{$field};
    }
}
