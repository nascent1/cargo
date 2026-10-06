<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoInventory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_lane_meters' => 'decimal:2',
        'used_lane_meters' => 'decimal:2',
        'held_lane_meters' => 'decimal:2',
        'blocked_lane_meters' => 'decimal:2',
        'total_weight_capacity_kg' => 'decimal:2',
        'used_weight_kg' => 'decimal:2',
        'held_weight_kg' => 'decimal:2',
        'total_volume_capacity_cbm' => 'decimal:2',
        'used_volume_cbm' => 'decimal:2',
        'held_volume_cbm' => 'decimal:2',
    ];

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    public function transportUnit()
    {
        return $this->belongsTo(TransportUnit::class);
    }
}
