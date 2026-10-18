<?php

namespace App\Services\DualControl\Handlers;

use App\Models\Consignment;
use App\Models\DualControlRequest;
use App\Services\Cargo\CargoBookingService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use Carbon\CarbonInterface;

/**
 * Cancels a cargo booking (releases its space, cancels its items) on behalf of the maker.
 */
class CargoBookingCancelHandler implements DualControlHandler
{
    public function __construct(private CargoBookingService $bookings)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $consignment = Consignment::find($request->resource_id) ?? throw new DualControlException('The booking no longer exists.');

        try {
            $this->bookings->cancelBooking($consignment, $request->maker_id, $request->payload_after['reason'] ?? null);
        } catch (DualControlException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DualControlException($e->getMessage());
        }
    }
}
