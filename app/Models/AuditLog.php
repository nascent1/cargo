<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'before_state', 'after_state', 'ip_address',
        'hash', 'previous_hash', 'sequence_number',
    ];

    protected $casts = [
        'before_state' => 'array', 'after_state' => 'array',
        'created_at' => 'datetime', 'sequence_number' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Mask sensitive fields from payloads before saving.
     */
    protected static function maskSensitiveData(?array $data): ?array
    {
        if (!$data) return null;
        
        $sensitiveKeys = ['password', 'password_confirmation', 'plain_password', 'token', 'pin', 'secret'];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = self::maskSensitiveData($value);
            }
        }
        
        return $data;
    }

    /**
     * Create a hash-chained audit entry.
     */
    public static function createEntry(
        ?int $userId, string $action, string $entityType, $entityId,
        ?array $beforeState = null, ?array $afterState = null, ?string $ipAddress = null
    ): self {
        $beforeState = self::maskSensitiveData($beforeState);
        $afterState = self::maskSensitiveData($afterState);

        $lastEntry = static::orderBy('sequence_number', 'desc')->first();
        $sequenceNumber = ($lastEntry?->sequence_number ?? 0) + 1;
        $previousHash = $lastEntry?->hash;

        $hashData = json_encode([
            'seq' => $sequenceNumber, 'user' => $userId, 'action' => $action,
            'entity' => $entityType, 'id' => $entityId,
            'before' => $beforeState, 'after' => $afterState,
            'prev_hash' => $previousHash, 'ts' => now()->toIso8601String(),
        ]);

        return static::create([
            'user_id' => $userId, 'action' => $action,
            'entity_type' => $entityType, 'entity_id' => $entityId,
            'before_state' => $beforeState, 'after_state' => $afterState,
            'ip_address' => $ipAddress, 'sequence_number' => $sequenceNumber,
            'previous_hash' => $previousHash, 'hash' => hash('sha256', $hashData),
        ]);
    }

    public function scopeForEntity($query, string $type, $id = null)
    {
        $query->where('entity_type', $type);
        if ($id) $query->where('entity_id', $id);
        return $query;
    }
}
