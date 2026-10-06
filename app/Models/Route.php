<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin_port_id',
        'destination_port_id',
        'name',
        'code',
        'distance_nm',
        'status',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'origin_port_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'destination_port_id');
    }

    public function cargoFares(): HasMany
    {
        return $this->hasMany(CargoFare::class);
    }

    public function transportServices(): HasMany
    {
        return $this->hasMany(TransportService::class);
    }

    public function agents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_routes')->withPivot('status')->withTimestamps();
    }
}
