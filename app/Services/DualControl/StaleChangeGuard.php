<?php

namespace App\Services\DualControl;

use Illuminate\Support\Str;

/**
 * A change is stale when a field it changes no longer holds the value the maker saw:
 * someone else changed the record after the request was submitted. Applying it would
 * silently overwrite their work, so the request is refused instead.
 */
class StaleChangeGuard
{
    private const IGNORED = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Keys whose value the request changes (after differs from before).
     */
    public static function changedKeys(?array $before, ?array $after): array
    {
        if (!$after) {
            return [];
        }
        if (!$before) {
            return array_keys($after);
        }

        return array_values(array_filter(
            array_keys($after),
            fn ($key) => !in_array($key, self::IGNORED, true) && !self::same($before[$key] ?? null, $after[$key])
        ));
    }

    /**
     * Changed keys whose current value differs from the value recorded at submission.
     */
    public static function staleKeys(?array $before, ?array $after, array $current): array
    {
        if (!$before) {
            return [];
        }

        return array_values(array_filter(
            self::changedKeys($before, $after),
            fn ($key) => array_key_exists($key, $current) && !self::same($before[$key] ?? null, $current[$key])
        ));
    }

    /**
     * Throws when the request is stale against the record's current values.
     */
    public static function assertFresh(?array $before, ?array $after, array $current): void
    {
        $stale = self::staleKeys($before, $after, $current);
        if ($stale) {
            $fields = implode(', ', array_map(fn ($k) => Str::of($k)->replace('_', ' ')->toString(), $stale));
            throw new DualControlException("The record changed after this request was submitted ({$fields}). Reject it and submit the change again.");
        }
    }

    public static function same(mixed $a, mixed $b): bool
    {
        $a = self::normalise($a);
        $b = self::normalise($b);

        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.00001;
        }

        return $a === $b;
    }

    private static function normalise(mixed $v): mixed
    {
        if (is_array($v)) {
            ksort($v);

            return json_encode(array_map(fn ($x) => self::normalise($x), $v));
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if ($v === null || $v === '') {
            return null;
        }
        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}T/', $v)) {
            // Model dates serialise as ISO strings; compare to the second
            return substr(str_replace('T', ' ', $v), 0, 19);
        }

        return is_scalar($v) ? (string) $v : json_encode($v);
    }
}
