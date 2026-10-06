<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargoFare extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'price_tzs' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'price_per_kg_tzs' => 'decimal:4',
        'price_per_kg_usd' => 'decimal:4',
        'price_per_cbm_tzs' => 'decimal:4',
        'price_per_cbm_usd' => 'decimal:4',
        'min_charge_tzs' => 'decimal:2',
        'min_charge_usd' => 'decimal:2',
    ];

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
}
