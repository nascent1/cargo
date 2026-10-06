<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DualControlConfig extends Model
{
    protected $fillable = [
        'operation_type',
        'description',
        'requires_dual_control',
    ];

    protected $casts = [
        'requires_dual_control' => 'boolean',
    ];
}
