<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $table = 'system_configs';

    protected $fillable = ['key_name', 'value', 'description', 'is_sensitive', 'group'];

    protected $casts = ['is_sensitive' => 'boolean'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::where('key_name', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key_name' => $key], ['value' => $value]);
    }

    protected static function booted()
    {
        static::updated(function ($config) {
            if ($config->isDirty('value')) {
                $old = ['key_name' => $config->key_name, 'value' => $config->getOriginal('value')];
                $new = ['key_name' => $config->key_name, 'value' => $config->value];
                
                \App\Models\AuditLog::createEntry(
                    \Illuminate\Support\Facades\Auth::id(),
                    'update',
                    'system_config',
                    $config->id,
                    $old,
                    $new,
                    request()?->ip() ?? '127.0.0.1'
                );
            }
        });
    }
}
