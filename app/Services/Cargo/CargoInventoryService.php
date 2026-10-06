<?php

namespace App\Services\Cargo;

use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\CargoType;
use App\Models\TransportService;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CargoInventoryService
{
    /**
     * Request a lock on cargo inventory for a specific service.
     * Uses pessimistic locking to prevent overbooking.
     */
    public function acquireLock(int $transportServiceId, array $items, int $userId, ?int $agentId = null): CargoLock
    {
        return DB::transaction(function () use ($transportServiceId, $items, $userId, $agentId) {
            // Pessimistic lock on inventory
            $inventory = CargoInventory::where('transport_service_id', $transportServiceId)
                ->lockForUpdate()
                ->firstOrFail();

            $totalWeightKg = 0;
            $totalVolumeCbm = 0;
            $totalLaneMeters = 0;

            foreach ($items as $item) {
                $cargoType = CargoType::find($item['cargo_type_id']);
                $totalWeightKg += $item['weight_kg'] ?? 0;
                $totalVolumeCbm += $item['volume_cbm'] ?? 0;
                
                if ($cargoType->measurement_unit === 'lane_meter') {
                    $totalLaneMeters += $cargoType->lane_meters;
                }
            }

            // Check availability
            $availableWeight = $inventory->total_weight_capacity_kg - ($inventory->used_weight_kg + $inventory->held_weight_kg);
            $availableVolume = $inventory->total_volume_capacity_cbm - ($inventory->used_volume_cbm + $inventory->held_volume_cbm);
            $availableLM = $inventory->total_lane_meters - ($inventory->used_lane_meters + $inventory->held_lane_meters);

            if ($totalWeightKg > $availableWeight || $totalVolumeCbm > $availableVolume || $totalLaneMeters > $availableLM) {
                throw new \Exception("Insufficient cargo capacity available on this transport service.");
            }

            // Update held amounts
            $inventory->held_weight_kg += $totalWeightKg;
            $inventory->held_volume_cbm += $totalVolumeCbm;
            $inventory->held_lane_meters += $totalLaneMeters;
            $inventory->save();

            // Determine lock duration based on user type (walk-in vs agent vs corporate)
            $holdMinutes = $this->getConfig('cargo_hold_minutes_walkin', 10);
            if ($agentId) {
                $holdMinutes = $this->getConfig('cargo_hold_minutes_agent', 15);
            }

            $lock = CargoLock::create([
                'transport_service_id' => $transportServiceId,
                'user_id' => $userId,
                'agent_id' => $agentId,
                'weight_kg' => $totalWeightKg,
                'volume_cbm' => $totalVolumeCbm,
                'lane_meters' => $totalLaneMeters,
                'expires_at' => now()->addMinutes($holdMinutes),
            ]);

            return $lock;
        });
    }

    /**
     * Commit a lock, turning held inventory into used inventory.
     */
    public function commitLock(CargoLock $lock)
    {
        DB::transaction(function () use ($lock) {
            $inventory = CargoInventory::where('transport_service_id', $lock->transport_service_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Move from held to used
            $inventory->held_weight_kg = max(0, $inventory->held_weight_kg - $lock->weight_kg);
            $inventory->held_volume_cbm = max(0, $inventory->held_volume_cbm - $lock->volume_cbm);
            $inventory->held_lane_meters = max(0, $inventory->held_lane_meters - $lock->lane_meters);

            $inventory->used_weight_kg += $lock->weight_kg;
            $inventory->used_volume_cbm += $lock->volume_cbm;
            $inventory->used_lane_meters += $lock->lane_meters;

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

    private function getConfig(string $key, int $default): int
    {
        return (int) Cache::remember("sys_cfg_{$key}", 3600, function() use ($key, $default) {
            $cfg = SystemConfig::where('key_name', $key)->first();
            return $cfg ? $cfg->value : $default;
        });
    }
}
