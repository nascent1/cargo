<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargoItem extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'length_m' => 'decimal:2',
        'width_m' => 'decimal:2',
        'height_m' => 'decimal:2',
        'volume_cbm' => 'decimal:4',
        'lane_meters_consumed' => 'decimal:2',
        'is_fragile' => 'boolean',
        'declared_value' => 'decimal:2',
        'base_fare' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'value_charge' => 'decimal:2',
        'value_charge_pct' => 'decimal:2',
        'unit_rate' => 'decimal:4',
        'min_charge' => 'decimal:2',
        'priced_at' => 'datetime',
        'fare_version' => 'integer',
        'pricing_snapshot' => 'array',
        'discount_amount' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'accepted_at' => 'datetime',
        'loaded_at' => 'datetime',
        'offloaded_at' => 'datetime',
        'collected_at' => 'datetime',
        'collection_id_verified' => 'boolean',
        'last_printed_at' => 'datetime',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function cargoType()
    {
        return $this->belongsTo(CargoType::class);
    }

    /**
     * The fare version used — for tracing only. The amounts charged live on the line itself
     * (unit_rate, value_charge_pct, min_charge, pricing_snapshot); never re-read prices from the fare.
     */
    public function fare()
    {
        return $this->belongsTo(CargoFare::class, 'fare_id')->withTrashed();
    }

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class);
    }

    /**
     * Accessor: expose qr_code_token as "barcode" for scanner/manifest compatibility.
     */
    public function getBarcodeAttribute(): ?string
    {
        return $this->qr_code_token;
    }

    /**
     * Scope to find by barcode (qr_code_token).
     */
    public function scopeWhereBarcode($query, string $barcode)
    {
        return $query->where('qr_code_token', $barcode);
    }
}
