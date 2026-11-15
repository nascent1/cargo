<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'city',
        'station_type',
        'has_parcel_office',
        'status',
    ];

    protected $casts = [
        'has_parcel_office' => 'boolean',
    ];

    // Cargo features usually don't link directly to Station for operational stuff, 
    // routes map origin/dest stations instead.

    /** Staff whose duty station this is. */
    public function staff()
    {
        return $this->hasMany(User::class);
    }

    /** "Dar es Salaam Port (DAR)". */
    public function getLabelAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
