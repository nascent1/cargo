<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * One version of the price for a cargo type on a route (optionally one vessel).
 *
 * A fare prices bookings held from effective_from (included) until effective_until (excluded, null = no end).
 * Prices are never edited in place: a change closes this version and adds the next one
 * (previous_fare_id / version), so every booking line's fare_id points at the price it was charged.
 */
class CargoFare extends Model
{
    use SoftDeletes;

    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';   // replaced or withdrawn before it started
    public const REVOKED = 'revoked';       // configured incorrectly, ended early
    public const INACTIVE = 'inactive';     // switched off under the old screen

    // Price fields; any change to one of these is a new version
    public const PRICE_FIELDS = [
        'price_tzs', 'price_usd', 'price_per_kg_tzs', 'price_per_kg_usd', 'price_per_cbm_tzs', 'price_per_cbm_usd',
        'min_charge_tzs', 'min_charge_usd', 'value_charge_pct',
    ];

    protected $guarded = ['id'];

    protected $appends = ['state'];

    protected $casts = [
        'price_tzs' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'price_per_kg_tzs' => 'decimal:4',
        'price_per_kg_usd' => 'decimal:4',
        'price_per_cbm_tzs' => 'decimal:4',
        'price_per_cbm_usd' => 'decimal:4',
        'min_charge_tzs' => 'decimal:2',
        'min_charge_usd' => 'decimal:2',
        'value_charge_pct' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'ended_at' => 'datetime',
        'version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CargoFare $fare) {
            $fare->effective_from ??= now()->startOfSecond(); // stored to the second; never rounded into the future
            $fare->created_by ??= Auth::id();
            $fare->status ??= self::ACTIVE;
        });
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function transportUnit()
    {
        return $this->belongsTo(TransportUnit::class);
    }

    public function cargoType()
    {
        return $this->belongsTo(CargoType::class);
    }

    public function previous()
    {
        return $this->belongsTo(self::class, 'previous_fare_id')->withTrashed();
    }

    public function next()
    {
        return $this->hasOne(self::class, 'previous_fare_id');
    }

    public function items()
    {
        return $this->hasMany(CargoItem::class, 'fare_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ender()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function dualControlRequest()
    {
        return $this->belongsTo(DualControlRequest::class);
    }

    /**
     * Active fares that price a booking made at $at.
     */
    public function scopeEffectiveAt(Builder $query, CarbonInterface $at): Builder
    {
        return $query->where('cargo_fares.status', self::ACTIVE)
            ->where('cargo_fares.effective_from', '<=', $at)
            ->where(fn ($q) => $q->whereNull('cargo_fares.effective_until')->orWhere('cargo_fares.effective_until', '>', $at));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->effectiveAt(now());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('cargo_fares.status', self::ACTIVE)->where('cargo_fares.effective_from', '>', now());
    }

    /**
     * Current and scheduled — everything that prices now or will.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('cargo_fares.status', self::ACTIVE)
            ->where(fn ($q) => $q->whereNull('cargo_fares.effective_until')->orWhere('cargo_fares.effective_until', '>', now()));
    }

    public function scopeSeries(Builder $query, int $cargoTypeId, int $routeId, ?int $transportUnitId): Builder
    {
        return $query->where('cargo_type_id', $cargoTypeId)->where('route_id', $routeId)
            ->where(fn ($q) => $transportUnitId ? $q->where('transport_unit_id', $transportUnitId) : $q->whereNull('transport_unit_id'));
    }

    /**
     * scheduled / current / expired for active fares; otherwise the status (cancelled, revoked, inactive).
     */
    public function getStateAttribute(): string
    {
        if ($this->status !== self::ACTIVE) {
            return $this->status;
        }
        if ($this->effective_from && $this->effective_from->isFuture()) {
            return 'scheduled';
        }
        if ($this->effective_until && !$this->effective_until->isFuture()) {
            return 'expired';
        }

        return 'current';
    }

    /**
     * Identifies a type + route + vessel for dual control (one open change at a time).
     */
    public static function seriesKey(int $cargoTypeId, int $routeId, ?int $transportUnitId): string
    {
        return "cargo_fare:{$cargoTypeId}:{$routeId}:" . ($transportUnitId ?: '-');
    }

    public function seriesKeyOf(): string
    {
        return self::seriesKey($this->cargo_type_id, $this->route_id, $this->transport_unit_id);
    }

    /**
     * The TZS rate the cargo type is priced by (flat, per kg or per cbm).
     */
    public function basisRate(?string $measurementUnit = null): float
    {
        return (float) match ($measurementUnit ?? $this->cargoType?->measurement_unit) {
            'kg' => $this->price_per_kg_tzs,
            'cbm' => $this->price_per_cbm_tzs,
            default => $this->price_tzs,
        };
    }

    public static function basisField(?string $measurementUnit): string
    {
        return match ($measurementUnit) {
            'kg' => 'price_per_kg_tzs',
            'cbm' => 'price_per_cbm_tzs',
            default => 'price_tzs',
        };
    }

    /**
     * Prices only — for comparing two versions.
     */
    public function prices(): array
    {
        return collect(self::PRICE_FIELDS)->mapWithKeys(fn ($f) => [$f => $this->{$f} === null ? null : (float) $this->{$f}])->all();
    }
}
