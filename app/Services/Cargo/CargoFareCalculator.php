<?php

namespace App\Services\Cargo;

use App\Models\CargoFare;
use App\Models\CargoType;
use App\Models\TransportService;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;

class CargoFareCalculator
{
    /**
     * Calculate fare for a list of cargo items intended for a specific transport service.
     */
    public function calculateForItems(array $itemData, int $transportServiceId, string $currency = 'TZS'): array
    {
        $transportService = TransportService::with(['route', 'transportUnit'])->findOrFail($transportServiceId);
        $routeId = $transportService->route_id;
        $unitId = $transportService->transport_unit_id;

        $results = [];
        $totalAmount = 0;
        $totalDiscount = 0;

        $hazmatFee = $this->getConfig("cargo_hazmat_flat_fee_" . strtolower($currency), 10000);
        $fragilePct = $this->getConfig("cargo_fragile_surcharge_pct", 5) / 100;
        $livestockPct = $this->getConfig("cargo_livestock_surcharge_pct", 0) / 100;
        
        $hasHazmat = false;
        $hasFragile = false;
        $hasLivestock = false;

        foreach ($itemData as $item) {
            $cargoType = CargoType::findOrFail($item['cargo_type_id']);
            
            // Find appropriate fare
            // Priority: specific unit -> specific route -> generic
            $fare = CargoFare::where('cargo_type_id', $cargoType->id)
                ->where(function($q) use ($routeId, $unitId) {
                    $q->where('transport_unit_id', $unitId)
                      ->orWhere('route_id', $routeId)
                      ->orWhere(function($sq) {
                          $sq->whereNull('transport_unit_id')
                             ->whereNull('route_id');
                      });
                })
                ->orderBy('transport_unit_id', 'desc')
                ->orderBy('route_id', 'desc')
                ->first();

            if (!$fare) {
                throw new \Exception("No fare configured for cargo type: {$cargoType->name}");
            }

            $baseFare = 0;

            if ($cargoType->measurement_unit === 'unit') {
                $baseFare = $currency === 'USD' ? $fare->price_usd : $fare->price_tzs;
            } elseif ($cargoType->measurement_unit === 'kg') {
                $rate = $currency === 'USD' ? $fare->price_per_kg_usd : $fare->price_per_kg_tzs;
                $baseFare = $item['weight_kg'] * $rate;
            } elseif ($cargoType->measurement_unit === 'cbm') {
                $rate = $currency === 'USD' ? $fare->price_per_cbm_usd : $fare->price_per_cbm_tzs;
                $baseFare = $item['volume_cbm'] * $rate;
            } elseif ($cargoType->measurement_unit === 'lane_meter') {
                // Not standard fare column, we might fallback to unit pricing or need per-meter
                // Standard fare uses price_tzs for flat vehicle rates.
                $baseFare = $currency === 'USD' ? $fare->price_usd : $fare->price_tzs;
            }

            $minCharge = $currency === 'USD' ? $fare->min_charge_usd : $fare->min_charge_tzs;
            if ($baseFare < $minCharge) {
                $baseFare = $minCharge;
            }

            $surcharge = 0;
            if ($cargoType->is_fragile || ($item['is_fragile'] ?? false)) {
                $surcharge += $baseFare * $fragilePct;
                $hasFragile = true;
            }

            if ($cargoType->requires_hazmat_declaration) {
                $surcharge += $hazmatFee; // flat fee per item
                $hasHazmat = true;
            }

            if ($cargoType->category === 'livestock') {
                $surcharge += $baseFare * $livestockPct;
                $hasLivestock = true;
            }

            $itemDiscount = $item['discount_amount'] ?? 0;
            $finalFare = ($baseFare + $surcharge) - $itemDiscount;

            $results[] = [
                'cargo_type_id' => $cargoType->id,
                'weight_kg' => $item['weight_kg'] ?? 0,
                'volume_cbm' => $item['volume_cbm'] ?? 0,
                'is_fragile' => $item['is_fragile'] ?? $cargoType->is_fragile,
                'fare_id' => $fare->id,
                'base_fare' => $baseFare,
                'surcharge_amount' => $surcharge,
                'discount_amount' => $itemDiscount,
                'final_fare' => $finalFare,
            ];

            $totalAmount += ($baseFare + $surcharge);
            $totalDiscount += $itemDiscount;
        }

        return [
            'currency' => $currency,
            'items' => $results,
            'total_amount' => $totalAmount,
            'total_discount' => $totalDiscount,
            'net_amount' => $totalAmount - $totalDiscount,
            'has_hazmat' => $hasHazmat,
            'has_fragile' => $hasFragile,
            'has_livestock' => $hasLivestock,
        ];
    }

    private function getConfig(string $key, $default)
    {
        return Cache::remember("sys_cfg_{$key}", 3600, function() use ($key, $default) {
            $cfg = SystemConfig::where('key_name', $key)->first();
            return $cfg ? $cfg->value : $default;
        });
    }
}
