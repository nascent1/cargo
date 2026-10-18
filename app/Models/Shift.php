<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sales-portal clerk's shift: starts when a sales officer signs in (otherwise at the first
 * booking action), runs for the configured hours (midnight never ends it), and is extended or
 * reopened only through an approved shift_extension request.
 */
class Shift extends Model
{
    public const END_EXPIRED = 'expired';
    public const END_SELF = 'self';
    public const END_ADMIN = 'admin';

    protected $fillable = [
        'user_id', 'start_time', 'end_time', 'actual_end_time', 'extended_minutes',
        'status', 'end_reason', 'ended_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'extended_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !($this->end_time && $this->end_time->isPast());
    }

    /** When the shift stopped (or stops), for summaries. */
    public function periodEnd(): \Carbon\CarbonInterface
    {
        return $this->actual_end_time ?? ($this->end_time && $this->end_time->isPast() ? $this->end_time : now());
    }

    public function endReasonLabel(): string
    {
        return match ($this->end_reason) {
            self::END_EXPIRED => 'Time ran out',
            self::END_SELF => 'Ended by clerk',
            self::END_ADMIN => 'Ended by ' . ($this->endedBy?->full_name ?? 'an administrator'),
            default => $this->status === 'active' ? 'On shift' : '—',
        };
    }

    /** Subject key for dual-control requests about this shift (one open extension at a time). */
    public function subjectKey(): string
    {
        return "shift:{$this->id}";
    }
}
