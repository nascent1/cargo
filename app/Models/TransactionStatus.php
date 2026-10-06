<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(SystemTransaction::class, 'transaction_status_id');
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    public static function getIdBySlug(string $slug, ?int $default = null): ?int
    {
        $status = self::findBySlug($slug);
        return $status ? $status->id : $default;
    }
}
