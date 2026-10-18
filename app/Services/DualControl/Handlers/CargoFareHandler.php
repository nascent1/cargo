<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Services\Cargo\CargoFareService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use Carbon\CarbonInterface;

/**
 * Applies an approved fare change by recomputing its plan for the moment it takes effect and
 * refusing if that differs from the plan the maker reviewed (CargoFareService::apply).
 */
class CargoFareHandler implements DualControlHandler
{
    public function __construct(private CargoFareService $fares)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $kind = array_search($request->operation_type, CargoFareService::OPERATIONS, true);
        $payload = $request->payload_after ?? [];
        if (!$kind || empty($payload['intent'])) {
            throw new DualControlException('This fare request is incomplete and cannot be applied.');
        }

        $result = $this->fares->apply($kind, $payload['intent'], $effectiveAt, $payload['plan_hash'] ?? null, $request);

        if ($request->resource_id === 0 && $result['created']) {
            $request->update(['resource_id' => $result['created'][0]]);
        }
    }
}
