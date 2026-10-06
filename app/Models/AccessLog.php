<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'access_logs';

    protected $fillable = [
        'user_id', 'event_type', 'ip_address', 'user_agent',
        'session_id', 'metadata',
    ];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function log(string $eventType, ?int $userId = null, ?array $metadata = null): self
    {
        return static::create([
            'user_id' => $userId, 'event_type' => $eventType,
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(), 'metadata' => $metadata,
        ]);
    }
}
