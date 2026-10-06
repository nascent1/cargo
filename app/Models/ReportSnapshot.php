<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'report_type',
        'report_data',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'report_data'   => 'array',
    ];
}
