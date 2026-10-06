<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdType extends Model
{
    protected $fillable = ['code', 'label', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}
