<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentAccount extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'agent_id',
        'account_number',
        'currency',
        'balance',
        'security_deposit_balance',
        'credit_limit',
        'is_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'security_deposit_balance' => 'decimal:4',
            'credit_limit' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(SystemTransaction::class, Agent::class, 'id', 'user_id', 'agent_id', 'user_id')->orderByDesc('id');
    }

    /**
     * Deduct balance and optionally record transaction.
     */
    public function deductBalance(float $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }

    /**
     * Calculate true available balance including credit limits and excluding active temporary holds.
     */
    public function getAvailableBalance(): float
    {
        $activeHeldAmount = (float) \App\Models\BookingLock::where('agent_account_id', $this->id)
            ->where('expires_at', '>', now())
            ->sum('amount_held');
            
        return (float) $this->balance + (float) $this->credit_limit - $activeHeldAmount;
    }
}
