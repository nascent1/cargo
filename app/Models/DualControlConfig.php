<?php

namespace App\Models;

use App\Services\DualControl\DualControlRegistry;
use Illuminate\Database\Eloquent\Model;

class DualControlConfig extends Model
{
    protected $fillable = [
        'operation_type',
        'description',
        'requires_dual_control',
        'module',
    ];

    protected $casts = [
        'requires_dual_control' => 'boolean',
    ];

    public function operation(): array
    {
        return DualControlRegistry::get($this->operation_type);
    }

    /**
     * Register every operation in config/dual_control.php. New rules get their registry default;
     * rules already in the table keep their on/off setting and only get the module and description updated.
     *
     * @return array{created: int, updated: int}
     */
    public static function syncFromRegistry(): array
    {
        $created = $updated = 0;

        foreach (DualControlRegistry::all() as $key => $op) {
            $config = static::firstOrNew(['operation_type' => $key]);
            $isNew = !$config->exists;

            $config->module = $op['module'];
            $config->description = $op['description'] ?? $config->description;
            if ($isNew) {
                $config->requires_dual_control = (bool) $op['default'];
            }

            if ($isNew || $config->isDirty()) {
                $config->save();
                $isNew ? $created++ : $updated++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
