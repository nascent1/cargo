<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'route', 'icon', 'parent_id', 'permission_name',
        'section', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(Menu::class, 'parent_id'); }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    public static function getMenuTree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function canAccess(?User $user): bool
    {
        if (!$user) return false;
        if (!$this->permission_name) return true;
        return $user->hasPermission($this->permission_name);
    }
}
