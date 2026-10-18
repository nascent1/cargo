<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Models\TransportService;
use App\Services\Cargo\CargoOperationsService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;

/**
 * Journey status. Departing or arriving also moves the cargo on board (CargoOperationsService)
 * and records the actual time, so it happens once, when the change is applied.
 */
class CargoJourneyStatusHandler implements DualControlHandler
{
    public function __construct(private CargoOperationsService $operations)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $service = TransportService::whereKey($request->resource_id)->lockForUpdate()->first()
            ?? throw new DualControlException('The departure no longer exists.');
        StaleChangeGuard::assertFresh($request->payload_before, $request->payload_after, ['status' => $service->status]);

        $old = $service->status;
        $new = $request->payload_after['status'];
        $attributes = ['status' => $new];
        if (in_array($new, ['departed', 'arrived'], true) && !$service->actual_departure_at) {
            $attributes['actual_departure_at'] = $effectiveAt;
        }
        if ($new === 'arrived') {
            $attributes['actual_arrival_at'] = $effectiveAt;
        }
        $service->update($attributes);

        // Arriving straight from "scheduled"/"boarding" still departs first: loaded cargo travels,
        // anything not loaded is left behind
        if ($new === 'departed' || ($new === 'arrived' && $old !== 'departed')) {
            $this->operations->departed($service, $request->maker_id, $effectiveAt);
        }
        if ($new === 'arrived') {
            $this->operations->arrived($service, $request->maker_id);
        }
    }
}
