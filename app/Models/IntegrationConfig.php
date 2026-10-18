<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credentials for an external provider: payment gateway, SMS gateway or SMTP server.
 *
 * Secrets are encrypted at rest with APP_KEY and hidden from toArray()/JSON. For channels with
 * several configs (sms, email) sort_order is the priority: 1 = primary, 2 = first failover, …
 */
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
        'sort_order'        => 'integer',
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
     * Read a catalog field key ('api_key' or 'extra.sender_id') off this row.
     */
    public function fieldValue(string $field): mixed
    {
        return str_starts_with($field, 'extra.') ? $this->extra(substr($field, 6)) : $this->{$field};
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Active configs for a channel, primary first.
     */
    public static function prioritised(string $channel): Collection
    {
        return static::where('channel', $channel)->active()->byPriority()->get();
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
