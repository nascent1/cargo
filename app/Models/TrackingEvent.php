<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    public $timestamps = false; // Manually handle created_at, no updated_at

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function cargoItem()
    {
        return $this->belongsTo(CargoItem::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
