<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'color',
        'sort_order',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(SystemTransaction::class, 'transaction_category_id');
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    public static function getIdBySlug(string $slug, ?int $default = null): ?int
    {
        $cat = self::findBySlug($slug);
        return $cat ? $cat->id : $default;
    }
}
