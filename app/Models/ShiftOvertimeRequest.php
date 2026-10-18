<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftOvertimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requested_hours',
        'status',
        'approved_by',
        'approved_at',
        'reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
