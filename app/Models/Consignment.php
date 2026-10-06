<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consignment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount_tzs' => 'decimal:2',
        'total_amount_usd' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'pricing_breakdown' => 'array',
        'total_weight_kg' => 'decimal:2',
        'total_volume_cbm' => 'decimal:4',
        'has_hazmat' => 'boolean',
        'has_fragile' => 'boolean',
        'has_livestock' => 'boolean',
        'estimated_arrival_at' => 'datetime',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CargoItem::class);
    }

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function bookedByUser()
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function bookedByAgent()
    {
        return $this->belongsTo(Agent::class, 'booked_by_agent_id');
    }

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function receipts()
    {
        return $this->hasMany(CargoReceipt::class);
    }

    public function paymentChannel()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'slug');
    }

    public function payments()
    {
        return $this->hasMany(CargoPayment::class);
    }

    public function openPayment()
    {
        // At most one attempt is open at a time (CargoPaymentService closes the previous one)
        return $this->hasOne(CargoPayment::class)->whereIn('status', CargoPayment::OPEN_STATUSES);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    // ── Presentation helpers (shared by admin profile, portal profile and receipt) ──

    /**
     * USD bookings store their total in total_amount_usd; everything else is TZS.
     */
    public function getBillingCurrencyAttribute(): string
    {
        return (float) $this->total_amount_usd > 0 ? 'USD' : 'TZS';
    }

    public function getBillingAmountAttribute(): float
    {
        return (float) ($this->billing_currency === 'USD' ? $this->total_amount_usd : $this->total_amount_tzs);
    }

    /**
     * Channel name from payment_methods, falling back to a readable slug for legacy values.
     */
    public function getPaymentMethodLabelAttribute(): ?string
    {
        if (!$this->payment_method) {
            return null;
        }

        return $this->paymentChannel?->name ?? \Illuminate\Support\Str::headline($this->payment_method);
    }

    public function isCancellable(): bool
    {
        return !in_array($this->status, ['cancelled', 'delivered', 'collected'], true);
    }
}
