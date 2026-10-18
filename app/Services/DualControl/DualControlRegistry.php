<?php

namespace App\Services\DualControl;

use Illuminate\Support\Str;

/**
 * Read access to config/dual_control.php — what each operation is, how it is timed and who applies it.
 */
class DualControlRegistry
{
    private const DEFAULTS = [
        'module' => 'Other', 'label' => null, 'description' => null, 'default' => true,
        'timing' => 'immediate', 'handler' => null, 'legacy' => false,
        'note' => null, // shown under the rule in Settings › Dual control
    ];

    public static function all(): array
    {
        return collect(config('dual_control.operations', []))
            ->map(fn (array $op, string $key) => self::normalise($key, $op))
            ->all();
    }

    public static function get(string $operation): array
    {
        $op = config("dual_control.operations.{$operation}");

        return self::normalise($operation, $op ?? ['module' => 'Unregistered', 'legacy' => false]) + ['registered' => $op !== null];
    }

    public static function has(string $operation): bool
    {
        return config("dual_control.operations.{$operation}") !== null;
    }

    public static function label(string $operation): string
    {
        return self::get($operation)['label'];
    }

    public static function timing(string $operation): string
    {
        return self::get($operation)['timing'];
    }

    /** True when the maker may choose when the change takes effect. */
    public static function canSchedule(string $operation): bool
    {
        return in_array(self::timing($operation), ['scheduled', 'dated'], true);
    }

    public static function handler(string $operation): ?string
    {
        return self::get($operation)['handler'];
    }

    public static function notEligible(): array
    {
        return config('dual_control.not_eligible', []);
    }

    private static function normalise(string $key, array $op): array
    {
        $op = array_merge(self::DEFAULTS, $op);
        $op['operation_type'] = $key;
        $op['label'] ??= Str::headline($key);

        return $op;
    }
}
