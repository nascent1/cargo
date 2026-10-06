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
     * Scope to filter out expired schedules.
     */
    public function scopeAvailableForBooking($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'departed', 'arrived'])
            ->where(function ($q) {
                $q->where('departure_date', '>', now()->toDateString())
                  ->orWhere(function ($q2) {
                      $q2->where('departure_date', '=', now()->toDateString())
                         ->where('departure_time', '>', now()->format('H:i:s'));
                  });
            });
    }
}
