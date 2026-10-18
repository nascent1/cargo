<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A closed business day and the close-of-business SMS sent for it.
 */
class BusinessDayClosure extends Model
{
    protected $fillable = [
        'business_date', 'period_start', 'period_end', 'trigger', 'last_service_id',
        'summary', 'deliveries', 'recipients_count', 'sent_count', 'failed_count', 'closed_by',
    ];

    protected $casts = [
        'business_date' => 'date',
        'period_start'  => 'datetime',
        'period_end'    => 'datetime',
        'summary'       => 'array',
        'deliveries'    => 'array',
    ];

    public function lastService()
    {
        return $this->belongsTo(TransportService::class, 'last_service_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
