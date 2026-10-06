<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DualControlRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'maker_id', 'checker_id', 'operation_type', 'resource_type',
        'resource_id', 'payload_before', 'payload_after', 'status',
        'checker_comment', 'expires_at', 'resolved_at',
    ];

    protected $casts = [
        'payload_before' => 'array', 'payload_after' => 'array',
        'expires_at' => 'datetime', 'resolved_at' => 'datetime',
    ];

    public function maker(): BelongsTo { return $this->belongsTo(User::class, 'maker_id'); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checker_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeActive($query) { return $query->where('status', 'pending')->where('expires_at', '>', now()); }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at && $this->expires_at->isPast();
    }
}
