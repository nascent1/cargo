<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoCapacity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_lane_meters' => 'decimal:2',
        'total_weight_capacity_kg' => 'decimal:2',
        'total_volume_capacity_cbm' => 'decimal:2',
        'max_payload_tons' => 'decimal:2',
    ];

    public function transportUnit()
    {
        return $this->belongsTo(TransportUnit::class);
    }
}
