<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'departure_time',
        'arrival_time',
        'status',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
