<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargoType extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'max_length_m' => 'decimal:2',
        'max_height_m' => 'decimal:2',
        'max_width_m' => 'decimal:2',
        'lane_meters' => 'decimal:2',
        'requires_hazmat_declaration' => 'boolean',
        'is_fragile' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function fares()
    {
        return $this->hasMany(CargoFare::class);
    }

    public function items()
    {
        return $this->hasMany(CargoItem::class);
    }
}
