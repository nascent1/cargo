<?php

namespace App\Models;

use App\Services\DualControl\DualControlRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DualControlRequest extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'pending' => 'Awaiting approval',
        'scheduled' => 'Scheduled',
        'approved' => 'Applied',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'maker_id', 'checker_id', 'operation_type', 'resource_type',
        'resource_id', 'subject_key', 'payload_before', 'payload_after', 'commence_at', 'status',
        'checker_comment', 'failure_reason', 'expires_at', 'resolved_at', 'executed_at',
    ];

    protected $casts = [
        'payload_before' => 'array', 'payload_after' => 'array',
        'commence_at' => 'datetime', 'executed_at' => 'datetime',
        'expires_at' => 'datetime', 'resolved_at' => 'datetime',
    ];

    public function maker(): BelongsTo { return $this->belongsTo(User::class, 'maker_id'); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checker_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeActive($query) { return $query->where('status', 'pending')->where('expires_at', '>', now()); }
    public function scopeOpen($query) { return $query->whereIn('status', ['pending', 'scheduled']); }

    /** @var array<string, self>|null open requests by subject_key, loaded once per request */
    private static ?array $openBySubject = null;

    /**
     * The pending or scheduled change for a record ("station:4"), for list badges. One query per page.
     */
    public static function openFor(string $subjectKey): ?self
    {
        self::$openBySubject ??= static::open()
            ->whereNotNull('subject_key')
            ->where(fn ($q) => $q->where('status', 'scheduled')->orWhere('expires_at', '>', now()))
            ->orderBy('id')
            ->get()
            ->keyBy('subject_key')
            ->all();

        return self::$openBySubject[$subjectKey] ?? null;
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at && $this->expires_at->isPast();
    }

    public function operation(): array
    {
        return DualControlRegistry::get($this->operation_type);
    }

    public function label(): string
    {
        return $this->operation()['label'];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Applied after the moment the maker asked for (approved late).
     */
    public function commencedLate(): bool
    {
        return $this->commence_at && $this->executed_at && $this->executed_at->gt($this->commence_at->copy()->addMinute());
    }

    /**
     * The moment the change takes / took effect.
     */
    public function takesEffectAt(): ?\Carbon\CarbonInterface
    {
        if ($this->commencedLate()) {
            return $this->executed_at;
        }

        return $this->commence_at ?? $this->executed_at;
    }
}
