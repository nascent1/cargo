<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoLock extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'lane_meters' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'volume_cbm' => 'decimal:2',
        'amount_held' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    public function cargoType()
    {
        return $this->belongsTo(CargoType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
