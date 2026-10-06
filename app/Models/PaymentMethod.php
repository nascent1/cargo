<?php

namespace App\Models;

use App\Gateways\Payment\Push\PushGatewayManager;
use App\Support\Msisdn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public const FLOWS = ['cash', 'ussd_push', 'lipa_namba'];

    protected $fillable = [
        'slug',
        'name',
        'is_active',
        'flow',
        'driver',
        'sort_order',
        'account_number',
        'account_name',
        'instructions',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Font Awesome icon for the channel, derived from its flow/slug
     * (payment_methods has no icon column).
     */
    public function getIconAttribute(): string
    {
        $slug = strtolower($this->slug ?? '');

        return match (true) {
            $slug === 'cash'                                         => 'fa-money-bill-wave',
            $this->flow === 'lipa_namba'                             => 'fa-store',
            str_contains($slug, 'bank')                              => 'fa-university',
            (bool) preg_match('/pesa|airtel|mobile/', $slug)         => 'fa-mobile-alt',
            (bool) preg_match('/card|visa|master/', $slug)           => 'fa-credit-card',
            (bool) preg_match('/agent|float|wallet/', $slug)         => 'fa-wallet',
            default                                                  => 'fa-coins',
        };
    }

    /**
     * Whether checkout can offer this channel. A push channel is offered even before its
     * credentials are live: the booking then waits as pending payment and the officer
     * takes the money through another channel.
     */
    public function isCheckoutReady(): bool
    {
        return match ($this->flow) {
            'cash'       => true,
            'lipa_namba' => filled($this->account_number),
            'ussd_push'  => isset(PushGatewayManager::DRIVERS[$this->driver]),
            default      => false,
        };
    }

    /**
     * Active, checkout-ready channels in the shape the booking screens consume.
     */
    public static function activeOptions(): array
    {
        $gateways = app(PushGatewayManager::class);
        $simulator = $gateways->simulatorAllowed();

        return static::active()
            ->whereIn('flow', self::FLOWS)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (self $m) => $m->isCheckoutReady())
            ->map(fn (self $m) => [
                'slug'      => $m->slug,
                'name'      => $m->name,
                'icon'      => $m->icon,
                'flow'      => $m->flow,
                'driver'    => $m->driver,
                // Number prefixes this push channel serves (empty = any network, e.g. Selcom Pesa)
                'prefixes'  => Msisdn::prefixesFor($m->driver),
                // live = a real push goes out; otherwise the simulator stands in (testing) or nothing is sent
                'live'      => $m->flow !== 'ussd_push' || $gateways->isConfigured($m->driver),
                'simulated' => $m->flow === 'ussd_push' && $simulator && !$gateways->isConfigured($m->driver),
            ])
            ->values()
            ->all();
    }
}
