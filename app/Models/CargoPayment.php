<?php

namespace App\Models;

use App\Support\Msisdn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One attempt to collect payment for a consignment (cash, USSD push or Lipa Namba).
 */
class CargoPayment extends Model
{
    public const FLOW_CASH = 'cash';
    public const FLOW_USSD = 'ussd_push';
    public const FLOW_LIPA = 'lipa_namba';

    public const OPEN_STATUSES = ['initiated', 'sending', 'awaiting_customer'];

    protected $guarded = ['id'];

    protected $casts = [
        'amount'          => 'decimal:2',
        'metadata'        => 'array',
        'expires_at'      => 'datetime',
        'last_checked_at' => 'datetime',
        'confirmed_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            $payment->uuid ??= (string) Str::uuid();
            $payment->reference ??= 'CP' . now()->format('ymd') . strtoupper(Str::random(8));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isSimulated(): bool
    {
        return (bool) ($this->metadata['simulated'] ?? false);
    }

    /**
     * Shape consumed by the payment panels (portal + admin) and their polling.
     */
    public function toApi(): array
    {
        $this->loadMissing(['paymentMethod', 'consignment', 'confirmedBy', 'initiatedBy']);
        $method = $this->paymentMethod;

        return [
            'uuid'               => $this->uuid,
            'reference'          => $this->reference,
            'status'             => $this->status,
            'flow'               => $this->flow,
            'driver'             => $this->driver,
            'simulated'          => $this->isSimulated(),
            'channel'            => ['slug' => $method?->slug, 'name' => $method?->name, 'icon' => $method?->icon],
            'amount'             => (float) $this->amount,
            'currency'           => $this->currency,
            'payer_msisdn'       => Msisdn::local($this->payer_msisdn),
            'payer_masked'       => Msisdn::mask($this->payer_msisdn),
            'provider_reference' => $this->provider_reference,
            'manual_reference'   => $this->manual_reference,
            'failure_reason'     => $this->failure_reason,
            'expires_at'         => $this->expires_at?->toIso8601String(),
            'seconds_left'       => $this->expires_at && $this->isOpen() ? max(0, now()->diffInSeconds($this->expires_at, false)) : null,
            'created_at'         => $this->created_at?->toIso8601String(),
            'confirmed_at'       => $this->confirmed_at?->toIso8601String(),
            'confirmed_by'       => $this->confirmedBy?->full_name,
            'initiated_by'       => $this->initiatedBy?->full_name,
            // Shown to the customer for Lipa Namba
            'lipa' => $this->flow === self::FLOW_LIPA && $method ? [
                'number'       => $method->account_number,
                'name'         => $method->account_name,
                'instructions' => $method->instructions,
            ] : null,
            'consignment' => [
                'id'                 => $this->consignment?->id,
                'consignment_number' => $this->consignment?->consignment_number,
                'status'             => $this->consignment?->status,
                'payment_status'     => $this->consignment?->payment_status,
                'sender_phone'       => $this->consignment?->sender_phone,
            ],
        ];
    }
}
