<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'agent_code',
        'agency_name',
        'contact_mobile',
        'email',
        'address',
        'agent_type',
        'agent_type_id',
        'commission_rate',
        'commission_method',
        'cancellation_refund_mode',
        'can_book_foreigners',
        'is_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'agent_type_id' => 'integer',
            'is_active' => 'boolean',
            'can_book_foreigners' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function associates(): HasMany
    {
        return $this->hasMany(AssociateAgent::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(AgentAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SystemTransaction::class, 'user_id', 'user_id')->orderByDesc('id');
    }

    public function ferries(): BelongsToMany
    {
        return $this->belongsToMany(Ferry::class, 'agent_ferries')->withPivot('status')->withTimestamps();
    }

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'agent_routes')->withPivot('status')->withTimestamps();
    }

    public function getEffectiveCommissionMethod(): string
    {
        return !empty($this->commission_method) ? $this->commission_method : \App\Models\SystemConfig::getValue('commission_default_method', 'post_sale');
    }

    public function getEffectiveCommissionRate(): float
    {
        return !is_null($this->commission_rate) ? (float) $this->commission_rate : (float) \App\Models\SystemConfig::getValue('commission_default_rate', 5.0);
    }

    public function getEffectiveSecurityDepositRate(): float
    {
        return (float) \App\Models\SystemConfig::getValue('security_deposit_default_rate', 40.0);
    }

    public function getTotalEarnedCommission(): float
    {
        $commCatId = \App\Models\TransactionCategory::getIdBySlug('commission');
        if (!$commCatId) {
            return 0.0;
        }
        return (float) \App\Models\SystemTransaction::where('user_id', $this->user_id)
            ->where('transaction_category_id', $commCatId)
            ->whereHas('paymentType', fn($q) => $q->where('slug', 'credit'))
            ->sum('amount_tzs');
    }
}
