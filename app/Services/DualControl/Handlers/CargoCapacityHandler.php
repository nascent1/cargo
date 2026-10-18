<?php

namespace App\Services\DualControl\Handlers;

use App\Http\Controllers\Cargo\Admin\JourneyController;
use App\Models\CargoInventory;
use App\Models\DualControlRequest;
use App\Models\TransportService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;

/**
 * Applies a departure capacity change. The row is locked, nobody's change since submission is
 * overwritten, and no total ends below what is already booked or held at that moment.
 */
class CargoCapacityHandler implements DualControlHandler
{
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $after = $request->payload_after ?? [];
        $service = TransportService::find($after['transport_service_id'] ?? null)
            ?? throw new DualControlException('The departure no longer exists.');

        $inventory = CargoInventory::where('transport_service_id', $service->id)->lockForUpdate()->first();
        if (!$inventory) {
            $inventory = CargoInventory::create(['transport_service_id' => $service->id, 'transport_unit_id' => $service->transport_unit_id]);
        }

        $totals = collect(JourneyController::DIMENSIONS)->pluck('total')->all();
        StaleChangeGuard::assertFresh($request->payload_before, array_intersect_key($after, array_flip($totals)), $inventory->only($totals));

        foreach (JourneyController::DIMENSIONS as $d) {
            $taken = (float) $inventory->{$d['used']} + (float) $inventory->{$d['held']};
            if ((float) $after[$d['total']] < $taken) {
                throw new DualControlException("{$d['label']} would drop below the {$taken} {$d['unit']} booked or held now. Reject and submit a new figure.");
            }
        }

        $inventory->update(array_intersect_key($after, array_flip($totals)));
        $request->update(['resource_id' => $inventory->id]);
    }
}
