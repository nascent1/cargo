<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id', 'channel', 'event', 'recipient', 'content',
        'status', 'provider', 'provider_message_id',
        'error_message', 'cost', 'sent_at', 'delivered_at',
    ];

    protected $casts = [
        'cost'         => 'decimal:4',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
