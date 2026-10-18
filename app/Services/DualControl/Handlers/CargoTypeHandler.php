<?php

namespace App\Services\DualControl\Handlers;

use App\Models\CargoType;
use App\Models\DualControlRequest;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class CargoTypeHandler implements DualControlHandler
{
    private const FIELDS = ['name', 'code', 'category', 'measurement_unit', 'lane_meters', 'max_weight_kg',
        'max_length_m', 'max_height_m', 'max_width_m', 'requires_hazmat_declaration', 'is_fragile', 'icon', 'sort_order', 'status'];

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $after = $request->payload_after ?? [];

        switch ($request->operation_type) {
            case 'cargo_type_create':
                if (CargoType::withTrashed()->where('code', $after['code'] ?? null)->exists()) {
                    throw new DualControlException("A cargo type with the code {$after['code']} already exists.");
                }
                $type = CargoType::create(Arr::only($after, self::FIELDS));
                $request->update(['resource_id' => $type->id, 'subject_key' => "cargo_type:{$type->id}"]);
                break;

            case 'cargo_type_update':
                $type = CargoType::find($request->resource_id) ?? throw new DualControlException('This cargo type no longer exists.');
                StaleChangeGuard::assertFresh($request->payload_before, $after, $type->attributesToArray());
                $changed = StaleChangeGuard::changedKeys($request->payload_before, $after);
                $type->update(Arr::only($after, array_intersect($changed, self::FIELDS)));
                break;

            case 'cargo_type_delete':
                $type = CargoType::find($request->resource_id);
                if ($type?->fares()->exists()) {
                    throw new DualControlException('This cargo type has fares, so it cannot be deleted. Set it inactive instead.');
                }
                $type?->delete();
                break;
        }
    }
}
