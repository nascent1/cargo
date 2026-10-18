<?php

namespace App\Services\DualControl;

use App\Models\DualControlRequest;
use App\Services\DualControlService;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Turns a request's before / after payloads into rows a checker can read.
 */
class DualControlPresenter
{
    private const HIDDEN = ['id', 'created_at', 'updated_at', 'deleted_at', 'actor_id', '_table', 'plan'];

    /**
     * @return array<int, array{key: string, label: string, before: string, after: string, changed: bool}>
     */
    public static function rows(DualControlRequest $request): array
    {
        $before = self::flatten($request->payload_before ?? []);
        $after = self::flatten($request->payload_after ?? []);
        $isCreate = !$request->payload_before;

        $rows = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            if (in_array(Str::before($key, '.'), self::HIDDEN, true)) {
                continue;
            }
            $b = $before[$key] ?? null;
            $a = array_key_exists($key, $after) ? $after[$key] : $b;
            $rows[] = [
                'key' => $key,
                'label' => Str::headline(str_replace('.', ' ', $key)),
                'before' => self::format($b),
                'after' => self::format($a),
                'changed' => $isCreate || DualControlService::isSealed($a) || !StaleChangeGuard::same($b, $a),
            ];
        }

        // What changes first, the rest for context
        usort($rows, fn ($x, $y) => $y['changed'] <=> $x['changed']);

        return $rows;
    }

    public static function format(mixed $value): string
    {
        if (DualControlService::isSealed($value)) {
            return '•••• new value (hidden)';
        }
        if ($value === '********') {
            return '•••• (hidden)';
        }
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            if (array_is_list($value) && count(array_filter($value, 'is_scalar')) === count($value)) {
                return implode(', ', $value) ?: '—';
            }

            return Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 240);
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            return Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y H:i');
        }

        return (string) $value;
    }

    /**
     * One level of nesting becomes dot keys (extra.password); deeper structures stay as values.
     */
    private static function flatten(array $data): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && !array_is_list($value) && !DualControlService::isSealed($value)
                && count(array_filter($value, fn ($v) => is_array($v) && !DualControlService::isSealed($v))) === 0) {
                foreach ($value as $k => $v) {
                    $flat["{$key}.{$k}"] = $v;
                }
            } else {
                $flat[(string) $key] = $value;
            }
        }

        return $flat;
    }

    /**
     * "Station #4" — what the request is about.
     */
    public static function subject(DualControlRequest $request): string
    {
        $type = Str::headline(str_replace('cargo_', '', $request->resource_type));

        return $request->resource_id > 0 ? "{$type} #{$request->resource_id}" : "New {$type}";
    }

    /**
     * Tailwind classes for a status pill.
     */
    public static function statusClasses(DualControlRequest $request): string
    {
        return match ($request->status) {
            'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
            'scheduled' => 'bg-sky-50 text-sky-800 border-sky-200',
            'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'failed', 'rejected' => 'bg-red-50 text-red-800 border-red-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
