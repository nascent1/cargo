<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'transport_type',
        'has_cargo_deck',
        'registration_number',
        'port_of_registry',
        'capacity',
        'status',
    ];

    public function services()
    {
        return $this->hasMany(TransportService::class);
    }

    public function cargoCapacities()
    {
        return $this->hasMany(CargoCapacity::class);
    }

    public function cargoFares()
    {
        return $this->hasMany(CargoFare::class);
    }

    // Still using old table name for agent assignments until Agent module is refactored
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_ferries')->withTimestamps();
    }
}
