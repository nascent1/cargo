<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransportService;
use App\Services\Cargo\CargoOperationsService;

/**
 * Safety net: departing a journey (Operations › Journeys) moves its cargo straight away. This catches
 * departures whose cargo wasn't moved (e.g. status set another way) — same rules, safe to repeat.
 */
class AutoTransitCargo extends Command
{
    protected $signature = 'cargo:auto-transit';
    protected $description = 'Move loaded cargo of departed journeys in transit and mark unloaded paid bookings left behind';

    public function handle(CargoOperationsService $operations)
    {
        // Departed in the last 12 hours (to catch up on cron misses) but not older
        $departedServices = TransportService::where('status', 'departed')
            ->where(function ($q) {
                $q->where('departure_date', '=', now()->toDateString())
                  ->where('departure_time', '<=', now()->toTimeString())
                  ->orWhere('departure_date', '<', now()->toDateString());
            })
            ->whereRaw("CONCAT(departure_date, ' ', departure_time) >= ?", [now()->subHours(12)])
            ->get();

        $moved = $left = 0;
        foreach ($departedServices as $service) {
            $result = $operations->departed($service, null, $service->actual_departure_at);
            $moved += $result['in_transit'];
            $left += $result['left_behind'];
        }

        $this->info("Moved {$moved} consignments to in_transit; {$left} left behind.");
    }
}
