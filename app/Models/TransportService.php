<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportService extends Model
{
    protected $table = 'transport_services';
    protected $fillable = [
        'schedule_id',
        'route_id',
        'transport_unit_id',
        'departure_date',
        'departure_time',
        'transport_type',
        'service_number',
        'status',
        'weather_notes',
        'actual_departure_at',
        'actual_arrival_at',
    ];

    protected $casts = [
        'actual_departure_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function transportUnit()
    {
        return $this->belongsTo(TransportUnit::class, 'transport_unit_id');
    }

    public function consignments()
    {
        return $this->hasMany(Consignment::class, 'transport_service_id');
    }

    public function cargoInventories()
    {
        return $this->hasMany(CargoInventory::class, 'transport_service_id');
    }

    public function cargoInventory()
    {
        return $this->hasOne(CargoInventory::class, 'transport_service_id');
    }

    /**
     * Departures that can still take bookings: not sailed or cancelled, on an active route,
     * and not on a vessel that is inactive or in maintenance.
     */
    public function scopeAvailableForBooking($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'departed', 'arrived'])
            ->whereHas('route', fn ($q) => $q->where('status', 'active'))
            ->whereDoesntHave('transportUnit', fn ($q) => $q->where('status', '!=', 'active'))
            ->where(function ($q) {
                $q->where('departure_date', '>', now()->toDateString())
                  ->orWhere(function ($q2) {
                      $q2->where('departure_date', '=', now()->toDateString())
                         ->where('departure_time', '>', now()->format('H:i:s'));
                  });
            });
    }
}
