<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoReceipt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'issued_at' => 'datetime',
        'metadata' => 'array',
        'amount_paid_tzs' => 'decimal:2',
        'amount_paid_usd' => 'decimal:2',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
