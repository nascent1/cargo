<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Models\Shift;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\ShiftService;
use Carbon\CarbonInterface;

/**
 * shift_extension: adds the approved hours to the clerk's running shift, or reopens the shift
 * that ended (from the moment of approval) — until the clerk may start a new shift anyway.
 * A clerk's own request always waits for a checker; a supervisor's follows the rule.
 */
class ShiftExtensionHandler implements DualControlHandler
{
    public function __construct(private ShiftService $shifts)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $shift = Shift::whereKey($request->resource_id)->lockForUpdate()->first();
        if (!$shift) {
            throw new DualControlException('The shift no longer exists.');
        }

        $a = $request->payload_after ?? [];
        try {
            $this->shifts->extend($shift, (float) ($a['hours'] ?? 0), $request->checker_id, $a['reason'] ?? null);
        } catch (\DomainException $e) {
            throw new DualControlException($e->getMessage());
        }
    }
}
