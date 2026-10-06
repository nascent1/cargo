<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::created(function ($schedule) {
            if ($schedule->status === 'active') {
                \Illuminate\Support\Facades\Artisan::call('cargo:generate-services', ['--days' => 30]);
            }
        });

        static::updated(function ($schedule) {
            if ($schedule->status === 'active' && $schedule->wasChanged(['status', 'start_date', 'end_date'])) {
                \Illuminate\Support\Facades\Artisan::call('cargo:generate-services', ['--days' => 30]);
            }
        });
    }

    protected $fillable = [
        'trip_id',
        'ferry_id',
        'start_date',
        'end_date',
        'frequency_pattern',
        'status',
    ];

    public function route(): BelongsTo
    {
        // Actually goes through trip
        return $this->belongsTo(Route::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function transportUnit(): BelongsTo
    {
        return $this->belongsTo(TransportUnit::class, 'ferry_id');
    }

    public function latestAction()
    {
        return $this->hasOne(DualControlRequest::class, 'resource_id', 'id')
                    ->where('resource_type', 'schedule')
                    ->latest('id');
    }
}
