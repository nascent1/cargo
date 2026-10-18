<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransportService;
use App\Services\Cargo\CargoOperationsService;

/**
 * Safety net: arriving a journey (Operations › Journeys) moves its cargo straight away. This catches
 * arrivals whose cargo wasn't moved — same rules, safe to repeat.
 */
class AutoArrivedCargo extends Command
{
    protected $signature = 'cargo:auto-arrived';
    protected $description = 'Move in_transit consignments to delivered (arrived at destination port) when vessel arrives';

    public function handle(CargoOperationsService $operations)
    {
        $arrivedServices = TransportService::where('status', 'arrived')
            ->whereRaw("CONCAT(departure_date, ' ', departure_time) >= ?", [now()->subDays(3)]) // Lookback window
            ->get();

        $count = 0;
        foreach ($arrivedServices as $service) {
            $count += $operations->arrived($service, null)['arrived'];
        }

        $this->info("Moved {$count} consignments to delivered (arrived at port).");
    }
}
