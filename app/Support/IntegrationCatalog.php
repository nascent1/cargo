<?php

namespace App\Support;

use App\Models\IntegrationConfig;
use App\Services\Sms\KilakonaDriver;
use App\Services\Sms\NextSmsDriver;

/**
 * SMS gateways and SMTP servers the Settings › Integrations screen can configure, and the fields each needs.
 * Payment providers are configured on Settings › Payment Channels (PushGatewayManager).
 *
 * Field keys map onto integration_configs columns ('api_key') or into
 * extra_credentials ('extra.sender_id'). Only providers with working code are listed.
 */
final class IntegrationCatalog
{
    public const CHANNELS = [
        'sms' => [
            'label'    => 'SMS Gateways',
            'icon'     => 'fa-comment-sms',
            'multiple' => true,
            'help'     => 'Every active gateway is used in priority order: if the primary fails, the next one is tried.',
        ],
        'email' => [
            'label'    => 'Email (SMTP)',
            'icon'     => 'fa-envelope',
            'multiple' => true,
            'help'     => 'Every active server is used in priority order: if the primary fails, the next one is tried.',
        ],
    ];

    public const PROVIDERS = [
        'kilakona' => [
            'channel'  => 'sms',
            'label'    => 'Kilakona SMS',
            'code'     => 'KILAKONA_SMS',
            'driver'   => KilakonaDriver::class,
            'uses_urls' => true,
            'urls'     => [
                'sandbox'    => 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send',
                'production' => 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send',
            ],
            'fields'   => [
                'api_key'         => ['label' => 'API Key', 'secret' => true, 'required' => true],
                'api_secret'      => ['label' => 'API Secret', 'secret' => true, 'required' => true],
                'extra.sender_id' => ['label' => 'Sender ID', 'required' => true, 'max' => 11],
            ],
        ],
        'nextsms' => [
            'channel'  => 'sms',
            'label'    => 'NextSMS',
            'code'     => 'NEXTSMS',
            'driver'   => NextSmsDriver::class,
            'uses_urls' => true,
            'urls'     => [
                'sandbox'    => 'https://messaging-service.co.tz',
                'production' => 'https://messaging-service.co.tz',
            ],
            'fields'   => [
                'access_token'    => ['label' => 'Bearer Token', 'secret' => true, 'required' => true],
                'extra.sender_id' => ['label' => 'Sender ID', 'required' => true, 'max' => 11],
            ],
        ],
        'smtp' => [
            'channel'  => 'email',
            'label'    => 'SMTP Server',
            'code'     => 'SMTP',
            'uses_urls' => false,
            'presets'  => [
                'zeptomail' => [
                    'label'  => 'ZeptoMail',
                    'values' => [
                        'extra.host'       => 'smtp.zeptomail.com',
                        'extra.port'       => '587',
                        'extra.encryption' => 'tls',
                        'extra.username'   => 'emailapikey',
                    ],
                    'note'   => 'The From address must be on a domain verified in ZeptoMail.',
                ],
            ],
            'fields'   => [
                'extra.host'         => ['label' => 'Host', 'required' => true, 'placeholder' => 'smtp.example.com'],
                'extra.port'         => ['label' => 'Port', 'required' => true, 'type' => 'number', 'placeholder' => '587'],
                'extra.encryption'   => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None']],
                'extra.username'     => ['label' => 'Username', 'required' => true],
                'api_secret'         => ['label' => 'Password', 'secret' => true, 'required' => true],
                'extra.from_address' => ['label' => 'From Address', 'required' => true, 'type' => 'email', 'placeholder' => 'no-reply@yourdomain.co.tz'],
                'extra.from_name'    => ['label' => 'From Name', 'placeholder' => 'UCLP Cargo'],
            ],
        ],
    ];

    public static function provider(?string $key): ?array
    {
        return $key !== null ? (self::PROVIDERS[$key] ?? null) : null;
    }

    /** @return array<string, array> providers for a channel, keyed by provider */
    public static function forChannel(string $channel): array
    {
        return array_filter(self::PROVIDERS, fn ($p) => $p['channel'] === $channel);
    }

    public static function allowsMultiple(string $channel): bool
    {
        return (bool) (self::CHANNELS[$channel]['multiple'] ?? false);
    }

    /** Form input name for a field key ('extra.sender_id' → 'f_extra__sender_id'). */
    public static function inputName(string $field): string
    {
        return 'f_' . str_replace('.', '__', $field);
    }

    /**
     * Labels of required fields (and the URL) that are still empty.
     *
     * @return string[]
     */
    public static function missing(IntegrationConfig $config): array
    {
        $meta = self::provider($config->provider);
        if (!$meta) {
            return ['Unknown provider'];
        }

        $missing = [];
        foreach ($meta['fields'] as $field => $f) {
            if (($f['required'] ?? false) && blank($config->fieldValue($field))) {
                $missing[] = $f['label'];
            }
        }
        if ($meta['uses_urls'] && !$config->activeUrl()) {
            $missing[] = ucfirst($config->environment ?? 'production') . ' URL';
        }

        return $missing;
    }

    public static function isComplete(IntegrationConfig $config): bool
    {
        return self::missing($config) === [];
    }

    /**
     * live | sandbox | incomplete | inactive
     */
    public static function status(IntegrationConfig $config): string
    {
        if (!$config->is_active) {
            return 'inactive';
        }
        if (!self::isComplete($config)) {
            return 'incomplete';
        }
        $meta = self::provider($config->provider);

        return ($meta['uses_urls'] ?? false) && $config->environment === 'sandbox' ? 'sandbox' : 'live';
    }

    public static function callbackUrl(IntegrationConfig $config): ?string
    {
        $path = self::provider($config->provider)['callback_path'] ?? null;

        return $path ? ($config->callback_url ?: url($path)) : null;
    }
}
