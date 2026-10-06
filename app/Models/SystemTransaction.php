<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'transaction_number',
        'transaction_category_id',
        'payment_type_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'amount_tzs',
        'balance_before',
        'balance_after',
        'payment_method_id',
        'payment_reference',
        'booking_id',
        'ticket_id',
        'consignment_id',
        'user_id',
        'transaction_status_id',
        'related_transaction_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_tzs' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relations
    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->transactionCategory();
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function transactionStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'transaction_status_id');
    }

    public function status(): BelongsTo
    {
        return $this->transactionStatus();
    }

    // public function paymentType(): BelongsTo
    // {
    //     return $this->belongsTo(PaymentType::class);
    // }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The original transaction this one relates to (e.g., refund → original sale).
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(SystemTransaction::class, 'related_transaction_id');
    }

    /**
     * Child transactions that reference this one (e.g., original sale → its refund).
     */
    public function childTransactions()
    {
        return $this->hasMany(SystemTransaction::class, 'related_transaction_id');
    }

    // Accessors for UI compatibility
    public function getCategoryAttribute(): string
    {
        return $this->transactionCategory?->slug ?? '';
    }

    public function getStatusAttribute(): string
    {
        return $this->transactionStatus?->slug ?? 'completed';
    }

    public function getPaymentChannelAttribute(): string
    {
        return $this->paymentMethod?->name ?? 'Cash';
    }

    public function getReferenceAttribute(): string
    {
        return $this->payment_reference ?? '-';
    }

    // Scopes for easy accounting reports
    public function scopeCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->whereHas('transactionCategory', fn($q) => $q->where('slug', $categorySlug));
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('transaction_status_id', 4);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
