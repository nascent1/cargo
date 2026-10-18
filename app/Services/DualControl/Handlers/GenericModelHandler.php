<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Models\Menu;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Station;
use App\Models\SystemConfig;
use App\Models\TransportUnit;
use App\Models\Trip;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Create / update / delete for plain records (stations, routes, trips, vessels, schedules, settings, menus).
 *
 * Updates apply only the fields the request changes, after checking nobody changed those
 * fields since the request was submitted (StaleChangeGuard).
 */
class GenericModelHandler implements DualControlHandler
{
    private const MODELS = [
        'station' => Station::class,
        'cargo_station' => Station::class,
        'route' => Route::class,
        'cargo_route' => Route::class,
        'trip' => Trip::class,
        'schedule' => Schedule::class,
        'cargo_transport_unit' => TransportUnit::class,
        'system_config' => SystemConfig::class,
        'menu' => Menu::class,
    ];

    // Sent with a vessel; stored on its cargo capacity row, not the vessel
    private const CAPACITY_KEYS = ['capacity_lane_meters', 'capacity_weight_kg', 'capacity_volume_cbm'];

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $class = self::MODELS[$request->resource_type] ?? null;
        if (!$class) {
            throw new DualControlException("This request is for an unknown record type ({$request->resource_type}).");
        }

        $op = $request->operation_type;
        match (true) {
            str_ends_with($op, '_create') => $this->create($request, $class),
            str_ends_with($op, '_delete') => $this->delete($request, $class),
            default => $this->update($request, $class),
        };
    }

    private function create(DualControlRequest $request, string $class): void
    {
        $payload = $request->payload_after ?? [];

        if ($class === Schedule::class && isset($payload['transport_unit_id']) && !isset($payload['ferry_id'])) {
            $payload['ferry_id'] = $payload['transport_unit_id'];
        }

        /** @var Model $model */
        $model = $class::create($this->fillable($class, Arr::except($payload, self::CAPACITY_KEYS)));

        if ($model instanceof TransportUnit && !empty($payload['has_cargo_deck'])) {
            $model->cargoCapacities()->create($this->capacityRow($payload));
        }

        $request->update(['resource_id' => $model->getKey(), 'subject_key' => "{$request->resource_type}:{$model->getKey()}"]);
    }

    private function update(DualControlRequest $request, string $class): void
    {
        $model = $class::find($request->resource_id);
        if (!$model) {
            throw new DualControlException('The record this request changes no longer exists.');
        }

        $before = $request->payload_before;
        $after = $request->payload_after ?? [];
        StaleChangeGuard::assertFresh($before, $after, $this->currentValues($model));

        $changed = StaleChangeGuard::changedKeys($before, $after);

        // Pivot lists are replaced as a whole
        foreach (['agents', 'routes', 'ferries'] as $relation) {
            if (in_array($relation, $changed, true) && method_exists($model, $relation)) {
                $model->{$relation}()->sync($after[$relation] ?? []);
            }
        }

        $attributes = $this->fillable($class, Arr::only($after, $changed));
        if ($attributes) {
            $model->update($attributes);
        }

        if ($model instanceof TransportUnit && $model->has_cargo_deck
            && (array_intersect(self::CAPACITY_KEYS, $changed) || !$model->cargoCapacities()->exists())) {
            $model->cargoCapacities()->updateOrCreate([], $this->capacityRow($after));
        }
    }

    private function delete(DualControlRequest $request, string $class): void
    {
        $class::find($request->resource_id)?->delete();
    }

    /**
     * The record as the maker saw it, including the extra keys some forms send (vessel capacity, route agents).
     */
    private function currentValues(Model $model): array
    {
        $current = $model->attributesToArray();

        if ($model instanceof TransportUnit) {
            $cap = $model->cargoCapacities()->first();
            $current['capacity_lane_meters'] = $cap->total_lane_meters ?? 0;
            $current['capacity_weight_kg'] = $cap->total_weight_capacity_kg ?? 0;
            $current['capacity_volume_cbm'] = $cap->total_volume_capacity_cbm ?? 0;
        }
        if ($model instanceof Route) {
            $current['agents'] = $model->agents()->get()->mapWithKeys(fn ($a) => [$a->id => ['status' => $a->pivot->status]])->toArray();
        }

        return $current;
    }

    private function capacityRow(array $payload): array
    {
        return [
            'total_lane_meters' => $payload['capacity_lane_meters'] ?? 0,
            'total_weight_capacity_kg' => $payload['capacity_weight_kg'] ?? 0,
            'total_volume_capacity_cbm' => $payload['capacity_volume_cbm'] ?? 0,
        ];
    }

    private function fillable(string $class, array $values): array
    {
        $fillable = (new $class)->getFillable();

        return $fillable ? Arr::only($values, $fillable) : $values;
    }
}
