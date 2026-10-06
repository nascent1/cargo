<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class IntegrationConfig extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'name', 'provider', 'channel', 'description', 'environment',
        'base_url', 'sandbox_url', 'production_url',
        'api_key', 'api_secret', 'access_token', 'merchant_id', 'passkey', 'extra_credentials',
        'callback_url', 'timeout_url', 'result_url',
        'is_active', 'is_default', 'currency', 'sort_order', 'icon', 'country_code',
        'metadata', 'updated_by'
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'is_default'        => 'boolean',
        'metadata'          => 'array',
        // Credentials are encrypted at rest (APP_KEY); reads through the model decrypt transparently
        'api_key'           => 'encrypted',
        'api_secret'        => 'encrypted',
        'access_token'      => 'encrypted',
        'passkey'           => 'encrypted',
        'extra_credentials' => 'encrypted:array',
    ];

    protected $hidden = ['api_key', 'api_secret', 'access_token', 'passkey', 'extra_credentials'];

    /**
     * URL for the selected environment (production_url / sandbox_url, falling back to base_url).
     */
    public function activeUrl(): ?string
    {
        $url = $this->environment === 'production' ? $this->production_url : $this->sandbox_url;

        return rtrim($url ?: (string) $this->base_url, '/') ?: null;
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        return ($this->extra_credentials ?? [])[$key] ?? $default;
    }

    /**
     * Get configuration for a specific provider.
     */
    public static function getProviderConfig(string $provider): ?array
    {
        $config = static::where('provider', $provider)->where('is_active', true)->first();
        return $config ? $config->toArray() : null;
    }
}
