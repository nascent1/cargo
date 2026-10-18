<?php

namespace App\Services\Cargo;

use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;

class CargoInventoryService
{
    public function __construct(protected CargoFareCalculator $calculator)
    {
    }

    /**
     * Hold space for a cart on a transport service.
     * Uses pessimistic locking to prevent overbooking. Passing $replaceLockId swaps an
     * existing hold (cart edited, or another departure chosen) in the same transaction,
     * so the old space is never left blocked.
     */
    public function acquireLock(int $transportServiceId, array $items, int $userId, ?int $agentId = null, ?int $replaceLockId = null): CargoLock
    {
        $need = $this->calculator->capacityFor($items);

        return DB::transaction(function () use ($transportServiceId, $need, $userId, $agentId, $replaceLockId) {
            if ($replaceLockId && ($old = CargoLock::where('id', $replaceLockId)->where('user_id', $userId)->first())) {
                $this->releaseLock($old);
            }

            $inventory = CargoInventory::where('transport_service_id', $transportServiceId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new \Exception('No cargo capacity has been configured for this departure.');
            }

            $available = $this->available($inventory);
            $short = [];
            if ($need['weight_kg'] > $available['weight_kg'] + 0.001) {
                $short[] = number_format($need['weight_kg'], 1) . ' kg needed, ' . number_format($available['weight_kg'], 1) . ' kg free';
            }
            if ($need['volume_cbm'] > $available['volume_cbm'] + 0.0001) {
                $short[] = number_format($need['volume_cbm'], 2) . ' cbm needed, ' . number_format($available['volume_cbm'], 2) . ' cbm free';
            }
            if ($need['lane_meters'] > $available['lane_meters'] + 0.001) {
                $short[] = number_format($need['lane_meters'], 1) . ' lane m needed, ' . number_format($available['lane_meters'], 1) . ' lane m free';
            }
            if ($short) {
                throw new \Exception('Not enough space on this departure: ' . implode('; ', $short) . '.');
            }

            $inventory->held_weight_kg += $need['weight_kg'];
            $inventory->held_volume_cbm += $need['volume_cbm'];
            $inventory->held_lane_meters += $need['lane_meters'];
            $inventory->save();

            // Hold duration depends on who is booking (walk-in counter vs agent)
            $holdMinutes = $agentId
                ? (int) SystemConfig::getValue('cargo_hold_minutes_agent', 15)
                : (int) SystemConfig::getValue('cargo_hold_minutes_walkin', 10);

            return CargoLock::create([
                'transport_service_id' => $transportServiceId,
                'user_id' => $userId,
                'agent_id' => $agentId,
                'weight_kg' => $need['weight_kg'],
                'volume_cbm' => $need['volume_cbm'],
                'lane_meters' => $need['lane_meters'],
                'expires_at' => now()->addMinutes($holdMinutes),
            ]);
        });
    }

    /**
     * Commit a lock, turning held inventory into used inventory.
     * $actual is the space the confirmed booking really uses; it may not exceed what was held.
     */
    public function commitLock(CargoLock $lock, ?array $actual = null)
    {
        $actual ??= ['weight_kg' => $lock->weight_kg, 'volume_cbm' => $lock->volume_cbm, 'lane_meters' => $lock->lane_meters];

        if ($actual['weight_kg'] > $lock->weight_kg + 0.001
            || $actual['volume_cbm'] > $lock->volume_cbm + 0.001
            || $actual['lane_meters'] > $lock->lane_meters + 0.001) {
            throw new \Exception('The cargo changed after space was held. Go back to the cargo step to hold space again.');
        }

        DB::transaction(function () use ($lock, $actual) {
            $inventory = CargoInventory::where('transport_service_id', $lock->transport_service_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Release the whole hold, then consume only what the booking uses
            $inventory->held_weight_kg = max(0, $inventory->held_weight_kg - $lock->weight_kg);
            $inventory->held_volume_cbm = max(0, $inventory->held_volume_cbm - $lock->volume_cbm);
            $inventory->held_lane_meters = max(0, $inventory->held_lane_meters - $lock->lane_meters);

            $inventory->used_weight_kg += $actual['weight_kg'];
            $inventory->used_volume_cbm += $actual['volume_cbm'];
            $inventory->used_lane_meters += $actual['lane_meters'];

            $inventory->save();

            $lock->delete();
        });
    }

    /**
     * Release a lock, freeing up held inventory.
     */
    public function releaseLock(CargoLock $lock)
    {
        DB::transaction(function () use ($lock) {
            $inventory = CargoInventory::where('transport_service_id', $lock->transport_service_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Remove from held
            $inventory->held_weight_kg = max(0, $inventory->held_weight_kg - $lock->weight_kg);
            $inventory->held_volume_cbm = max(0, $inventory->held_volume_cbm - $lock->volume_cbm);
            $inventory->held_lane_meters = max(0, $inventory->held_lane_meters - $lock->lane_meters);

            $inventory->save();

            $lock->delete();
        });
    }

    /**
     * Return booked space to a departure (cancellation).
     */
    public function releaseUsed(int $transportServiceId, float $weightKg, float $volumeCbm, float $laneMeters): void
    {
        $inventory = CargoInventory::where('transport_service_id', $transportServiceId)->lockForUpdate()->first();
        if (!$inventory) {
            return;
        }

        $inventory->used_weight_kg = max(0, $inventory->used_weight_kg - $weightKg);
        $inventory->used_volume_cbm = max(0, $inventory->used_volume_cbm - $volumeCbm);
        $inventory->used_lane_meters = max(0, $inventory->used_lane_meters - $laneMeters);
        $inventory->save();
    }

    /**
     * Free space = total − used − held.
     */
    /**
     * Free space per dimension, plus a one-line summary of the fullest dimension it carries.
     * A departure is only "full" when every dimension it carries is used up.
     *
     * @return array{0: ?array, 1: ?array} [capacity per dimension, summary]
     */
    public function spaceSummary(?CargoInventory $inventory): array
    {
        if (!$inventory) {
            return [null, null];
        }

        $free = $this->available($inventory);
        $capacity = collect([
            'weight_kg' => 'total_weight_capacity_kg',
            'volume_cbm' => 'total_volume_capacity_cbm',
            'lane_meters' => 'total_lane_meters',
        ])->map(fn ($totalCol, $key) => [
            'total' => (float) $inventory->{$totalCol},
            'free' => max(0, round($free[$key], 2)),
        ]);

        $carried = $capacity->filter(fn ($c) => $c['total'] > 0);
        if ($carried->isEmpty()) {
            return [null, null];
        }

        $pct = $carried->map(fn ($c) => (int) min(100, round(($c['total'] - $c['free']) / $c['total'] * 100)));
        $fullest = $pct->sortDesc()->keys()->first();

        return [$capacity->all(), [
            'pct' => $pct[$fullest],
            'dimension' => $fullest,
            'free' => $capacity[$fullest]['free'],
            'full' => $carried->every(fn ($c) => $c['free'] <= 0),
        ]];
    }

    public function available(CargoInventory $inventory): array
    {
        return [
            'weight_kg' => (float) $inventory->total_weight_capacity_kg - (float) $inventory->used_weight_kg - (float) $inventory->held_weight_kg,
            'volume_cbm' => (float) $inventory->total_volume_capacity_cbm - (float) $inventory->used_volume_cbm - (float) $inventory->held_volume_cbm,
            'lane_meters' => (float) $inventory->total_lane_meters - (float) $inventory->used_lane_meters - (float) $inventory->held_lane_meters,
        ];
    }
}
