<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'employee_id', 'department', 'position', 'hire_date', 'status',
    ];

    protected $casts = ['hire_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->full_name ?? '';
    }
}
