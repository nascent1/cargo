<?php

namespace App\Services\Cargo;

use App\Exceptions\CargoPricingException;
use App\Models\CargoFare;
use App\Models\CargoType;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Services\ExchangeRateService;
use Carbon\CarbonInterface;

/**
 * The single cargo pricing engine, shared by the admin booking terminal and the sales portal.
 *
 * A line is one cargo type booked in some quantity:
 *   cargo_type_id, quantity (default 1),
 *   weight_kg   — total weight of the whole line,
 *   volume_cbm  — total volume of the line, or length_m/width_m/height_m of ONE piece (× quantity),
 *   is_fragile  — optional, on top of the type's own flag,
 *   declared_value — value of the goods in the booking currency; required when the fare has a value charge.
 *
 * Line price = base (minimum charge applies here) + handling surcharges + value charge (fare % × declared value).
 *
 * Prices are never taken from the client. Discounts only come from the server-authorised
 * $discount argument (fare override), split across lines pro rata.
 *
 * The fare is the version in force at the booking moment ($pricedAt: when space was held; now for a quote).
 * Each line carries a full copy of how it was priced (pricing_snapshot), which is what the booking stores.
 */
class CargoFareCalculator
{
    public function calculateForItems(array $itemData, int $transportServiceId, string $currency = 'TZS', float $discount = 0, ?CarbonInterface $pricedAt = null): array
    {
        $currency = strtoupper($currency);
        $service = TransportService::with(['route:id,name,code', 'transportUnit:id,name'])->findOrFail($transportServiceId);
        $pricedAt ??= now();

        $hazmatFee = $this->hazmatFee($currency);
        $fragilePct = (float) SystemConfig::getValue('cargo_fragile_surcharge_pct', 5) / 100;
        $livestockPct = (float) SystemConfig::getValue('cargo_livestock_surcharge_pct', 0) / 100;

        $types = CargoType::whereIn('id', collect($itemData)->pluck('cargo_type_id')->filter()->unique())->get()->keyBy('id');
        $errors = [];
        $lines = [];

        foreach (array_values($itemData) as $i => $item) {
            $type = $types->get($item['cargo_type_id'] ?? null);
            if (!$type || $type->status !== 'active') {
                $errors["items.{$i}.cargo_type_id"] = 'Select an active cargo type.';
                continue;
            }

            $capacity = $this->lineCapacity($item, $type);
            $lineErrors = $this->validateLine($i, $type, $capacity);
            if ($lineErrors) {
                $errors += $lineErrors;
                continue;
            }

            $fare = $this->resolveFare($type->id, $service->route_id, $service->transport_unit_id, $pricedAt);
            if (!$fare) {
                $errors["items.{$i}.cargo_type_id"] = "No fare is set for {$type->name} on this route.";
                continue;
            }

            [$base, $method, $rate] = $this->baseFare($type, $fare, $capacity, $currency);
            // A fare without a rate for the type's pricing basis would book the line for free (same rule as pricedTypeIds)
            if ($fare->basisRate($type->measurement_unit) <= 0) {
                $errors["items.{$i}.cargo_type_id"] = "No price is set for {$type->name} on this route: its fare has no "
                    . ['per_kg' => 'per-kg rate', 'per_cbm' => 'per-cbm rate', 'flat' => 'price'][$method] . '. Set it under Cargo Fares.';
                continue;
            }

            $minCharge = (float) ($currency === 'USD' ? $fare->min_charge_usd : $fare->min_charge_tzs);
            if ($minCharge > 0 && $base < $minCharge) {
                $base = $minCharge;
                $method = 'minimum';
            }
            $base = $this->round($base, $currency);

            $isFragile = $type->is_fragile || !empty($item['is_fragile']);
            $surcharges = [];
            if ($isFragile && $fragilePct > 0) {
                $surcharges[] = ['label' => 'Fragile handling (' . ($fragilePct * 100) . '%)', 'amount' => $this->round($base * $fragilePct, $currency)];
            }
            if ($type->requires_hazmat_declaration && $hazmatFee > 0) {
                $surcharges[] = ['label' => 'Hazmat declaration', 'amount' => $this->round($hazmatFee, $currency)];
            }
            if ($type->category === 'livestock' && $livestockPct > 0) {
                $surcharges[] = ['label' => 'Livestock handling (' . ($livestockPct * 100) . '%)', 'amount' => $this->round($base * $livestockPct, $currency)];
            }
            $surcharge = array_sum(array_column($surcharges, 'amount'));

            [$declared, $valuePct, $valueCharge, $valueError] = $this->valueCharge($item, $fare, $type, $currency);
            if ($valueError) {
                $errors["items.{$i}.declared_value"] = $valueError;
                continue;
            }

            $lines[] = array_merge($capacity, [
                'cargo_type_id'    => $type->id,
                'fare_version'     => $fare->version,
                'min_charge'       => $minCharge > 0 ? $minCharge : null,
                'priced_at'        => $pricedAt->toIso8601String(),
                'pricing_snapshot' => $this->snapshot($fare, $type, $service, $currency, $pricedAt, $surcharges, $fragilePct, $hazmatFee, $livestockPct),
                'cargo_type_name'  => $type->name,
                'measurement_unit' => $type->measurement_unit,
                'category'         => $type->category,
                'is_fragile'       => $isFragile,
                'is_hazmat'        => (bool) $type->requires_hazmat_declaration,
                'fare_id'          => $fare->id,
                'pricing_method'   => $method,
                'unit_rate'        => $rate,
                'base_fare'        => $base,
                'surcharge_amount' => $surcharge,
                'surcharges'       => $surcharges,
                'declared_value'   => $declared,
                'value_charge_pct' => $valuePct,
                'value_charge'     => $valueCharge,
                'gross_fare'       => $base + $surcharge + $valueCharge,
                'discount_amount'  => 0,
                'final_fare'       => $base + $surcharge + $valueCharge,
            ]);
        }

        if ($errors) {
            throw new CargoPricingException($errors);
        }

        $gross = array_sum(array_column($lines, 'gross_fare'));
        $discount = $this->round(max(0, $discount), $currency);
        if ($discount > $gross) {
            throw new CargoPricingException(['discount_amount' => 'The discount cannot be more than the total fare.']);
        }
        $lines = $this->applyDiscount($lines, $discount, $currency);

        return [
            'currency'       => $currency,
            'items'          => $lines,
            'total_amount'   => $gross,
            'total_discount' => $discount,
            'net_amount'     => $gross - $discount,
            'totals'         => [
                'lines'       => count($lines),
                'pieces'      => array_sum(array_column($lines, 'quantity')),
                'weight_kg'   => round(array_sum(array_column($lines, 'weight_kg')), 2),
                'volume_cbm'  => round(array_sum(array_column($lines, 'volume_cbm')), 4),
                'lane_meters' => round(array_sum(array_column($lines, 'lane_meters_consumed')), 2),
                'value_charge' => array_sum(array_column($lines, 'value_charge')),
            ],
            'has_hazmat'     => collect($lines)->contains('is_hazmat', true),
            'has_fragile'    => collect($lines)->contains('is_fragile', true),
            'has_livestock'  => collect($lines)->contains('category', 'livestock'),
        ];
    }

    /**
     * Space a cart needs on a transport service (used to hold inventory). No fare lookup.
     */
    public function capacityFor(array $itemData): array
    {
        $types = CargoType::whereIn('id', collect($itemData)->pluck('cargo_type_id')->filter()->unique())->get()->keyBy('id');
        $totals = ['weight_kg' => 0.0, 'volume_cbm' => 0.0, 'lane_meters' => 0.0];

        foreach ($itemData as $item) {
            $type = $types->get($item['cargo_type_id'] ?? null);
            if (!$type) {
                continue;
            }
            $line = $this->lineCapacity($item, $type);
            $totals['weight_kg'] += $line['weight_kg'];
            $totals['volume_cbm'] += $line['volume_cbm'];
            $totals['lane_meters'] += $line['lane_meters_consumed'];
        }

        return array_map(fn ($v) => round($v, 4), $totals);
    }

    /**
     * Cargo types that can be booked on a route (and vessel) at a moment: those whose fare in force
     * has a rate for the type's pricing basis (per kg, per cbm or flat).
     *
     * @return int[]
     */
    public function pricedTypeIds(int $routeId, ?int $transportUnitId, ?CarbonInterface $at = null): array
    {
        return CargoFare::with('cargoType:id,measurement_unit')
            ->effectiveAt($at ?? now())
            ->where('route_id', $routeId)
            ->where(fn ($q) => $q->whereNull('transport_unit_id')->when($transportUnitId, fn ($q) => $q->orWhere('transport_unit_id', $transportUnitId)))
            ->orderByRaw('transport_unit_id IS NULL')
            ->orderByDesc('id')
            ->get()
            ->unique('cargo_type_id') // the fare resolveFare() picks: this vessel's first, then the route-wide one
            ->filter(fn (CargoFare $f) => $f->basisRate() > 0)
            ->map(fn (CargoFare $f) => (int) $f->cargo_type_id)
            ->values()
            ->all();
    }

    /**
     * Most specific fare in force at $at for the type on this route: this vessel's fare first, then the route-wide one.
     */
    public function resolveFare(int $cargoTypeId, int $routeId, ?int $transportUnitId, ?CarbonInterface $at = null): ?CargoFare
    {
        return CargoFare::effectiveAt($at ?? now())
            ->where('cargo_type_id', $cargoTypeId)
            ->where('route_id', $routeId)
            ->where(fn ($q) => $q->whereNull('transport_unit_id')
                ->when($transportUnitId, fn ($q) => $q->orWhere('transport_unit_id', $transportUnitId)))
            ->orderByRaw('transport_unit_id IS NULL')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Everything needed to explain the line's price later without reading the fare row.
     */
    private function snapshot(CargoFare $fare, CargoType $type, TransportService $service, string $currency, CarbonInterface $pricedAt,
        array $surcharges, float $fragilePct, float $hazmatFee, float $livestockPct): array
    {
        return [
            'source' => 'calculator',
            'priced_at' => $pricedAt->toIso8601String(),
            'currency' => $currency,
            'fare' => [
                'id' => $fare->id,
                'version' => $fare->version,
                'from' => $fare->effective_from?->toIso8601String(),
                'until' => $fare->effective_until?->toIso8601String(),
                'vessel_specific' => $fare->transport_unit_id !== null,
            ],
            'type' => ['id' => $type->id, 'name' => $type->name, 'code' => $type->code, 'unit' => $type->measurement_unit, 'category' => $type->category],
            'route' => ['id' => $service->route_id, 'name' => $service->route?->name, 'code' => $service->route?->code],
            'vessel' => ['id' => $service->transport_unit_id, 'name' => $service->transportUnit?->name],
            'rates' => $fare->prices(),
            'surcharges' => $surcharges,
            'settings' => ['fragile_pct' => $fragilePct * 100, 'hazmat_fee' => $hazmatFee, 'livestock_pct' => $livestockPct * 100],
        ];
    }

    private function lineCapacity(array $item, CargoType $type): array
    {
        $qty = max(1, (int) ($item['quantity'] ?? 1));
        $l = (float) ($item['length_m'] ?? 0);
        $w = (float) ($item['width_m'] ?? 0);
        $h = (float) ($item['height_m'] ?? 0);

        // Dimensions describe one piece; an explicit volume is already the line total
        $volume = ($l > 0 && $w > 0 && $h > 0) ? $l * $w * $h * $qty : (float) ($item['volume_cbm'] ?? 0);

        return [
            'quantity'             => $qty,
            'weight_kg'            => round(max(0, (float) ($item['weight_kg'] ?? 0)), 2),
            'volume_cbm'           => round(max(0, $volume), 4),
            'length_m'             => $l ?: null,
            'width_m'              => $w ?: null,
            'height_m'             => $h ?: null,
            'lane_meters_consumed' => round((float) $type->lane_meters * $qty, 2),
        ];
    }

    private function validateLine(int $i, CargoType $type, array $capacity): array
    {
        $errors = [];

        if ($type->measurement_unit === 'kg' && $capacity['weight_kg'] <= 0) {
            $errors["items.{$i}.weight_kg"] = "Enter the weight — {$type->name} is priced per kg.";
        }
        if ($type->measurement_unit === 'cbm' && $capacity['volume_cbm'] <= 0) {
            $errors["items.{$i}.volume_cbm"] = "Enter the dimensions — {$type->name} is priced per cubic metre.";
        }

        $maxPerPiece = (float) $type->max_weight_kg;
        if ($maxPerPiece > 0 && $capacity['weight_kg'] / $capacity['quantity'] > $maxPerPiece) {
            $errors["items.{$i}.weight_kg"] = sprintf(
                '%s allows up to %s kg per piece (%s kg entered for %d piece%s).',
                $type->name, number_format($maxPerPiece), number_format($capacity['weight_kg'], 1),
                $capacity['quantity'], $capacity['quantity'] > 1 ? 's' : ''
            );
        }

        return $errors;
    }

    /**
     * @return array{0: float, 1: string, 2: float} base fare, pricing method, unit rate
     */
    private function baseFare(CargoType $type, CargoFare $fare, array $capacity, string $currency): array
    {
        $usd = $currency === 'USD';

        return match ($type->measurement_unit) {
            'kg'  => [($rate = (float) ($usd ? $fare->price_per_kg_usd : $fare->price_per_kg_tzs)) * $capacity['weight_kg'], 'per_kg', $rate],
            'cbm' => [($rate = (float) ($usd ? $fare->price_per_cbm_usd : $fare->price_per_cbm_tzs)) * $capacity['volume_cbm'], 'per_cbm', $rate],
            // unit and lane_meter (vehicles): flat price per piece
            default => [($rate = (float) ($usd ? $fare->price_usd : $fare->price_tzs)) * $capacity['quantity'], 'flat', $rate],
        };
    }

    /**
     * Fare % of the declared value. With a % set, the declared value must be entered (0 is allowed).
     *
     * @return array{0: ?float, 1: float, 2: float, 3: ?string} declared value, %, charge, error
     */
    private function valueCharge(array $item, CargoFare $fare, CargoType $type, string $currency): array
    {
        $raw = $item['declared_value'] ?? null;
        $declared = ($raw === null || $raw === '') ? null : (float) $raw;
        $pct = (float) $fare->value_charge_pct;

        if ($declared !== null && ($declared < 0 || $declared > 9999999999)) {
            return [null, $pct, 0, 'Enter a declared value between 0 and 9,999,999,999.'];
        }
        if ($pct <= 0) {
            return [$declared, 0, 0, null];
        }
        if ($declared === null) {
            return [null, $pct, 0, sprintf('Enter the declared value — the %s fare charges %s%% of it.', $type->name, rtrim(rtrim(number_format($pct, 2), '0'), '.'))];
        }

        return [$declared, $pct, $this->round($declared * $pct / 100, $currency), null];
    }

    private function applyDiscount(array $lines, float $discount, string $currency): array
    {
        $gross = array_sum(array_column($lines, 'gross_fare'));
        if ($discount <= 0 || $gross <= 0) {
            return $lines;
        }

        $remaining = $discount;
        $last = count($lines) - 1;
        foreach ($lines as $i => &$line) {
            // Pro rata; the last line takes the rounding remainder so the parts add up exactly
            $share = $i === $last ? $remaining : $this->round($discount * $line['gross_fare'] / $gross, $currency);
            $share = min($share, $line['gross_fare']);
            $line['discount_amount'] = $share;
            $line['final_fare'] = $line['gross_fare'] - $share;
            $remaining -= $share;
        }

        return $lines;
    }

    private function hazmatFee(string $currency): float
    {
        $fee = SystemConfig::getValue('cargo_hazmat_flat_fee_' . strtolower($currency));
        if ($fee !== null) {
            return (float) $fee;
        }

        $tzs = (float) SystemConfig::getValue('cargo_hazmat_flat_fee_tzs', 10000);

        return $currency === 'TZS' ? $tzs : $tzs / max(1, ExchangeRateService::getCurrentRate('USD', 'TZS'));
    }

    private function round(float $amount, string $currency): float
    {
        return round($amount, $currency === 'TZS' ? 0 : 2);
    }
}
