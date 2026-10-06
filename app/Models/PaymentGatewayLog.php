<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    protected $fillable = [
        'payment_id',
        'booking_id',
        'consignment_id',
        'cargo_payment_id',
        'gateway',
        'event_type',
        'direction',
        'http_method',
        'url',
        'request_headers',
        'request_payload',
        'response_payload',
        'http_status_code',
        'transaction_reference',
        'amount',
        'currency',
        'is_success',
        'error_message',
        'response_time_ms',
    ];

    protected $casts = [
        'request_headers'  => 'array',
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'amount'           => 'decimal:2',
        'is_success'       => 'boolean',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
