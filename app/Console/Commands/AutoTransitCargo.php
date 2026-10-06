<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransportService;
use App\Services\Cargo\CargoTrackingService;
use Carbon\Carbon;

class AutoTransitCargo extends Command
{
    protected $signature = 'cargo:auto-transit';
    protected $description = 'Automatically move loaded consignments to in_transit when the transport service departs';

    public function handle(CargoTrackingService $trackingService)
    {
        // Find transport services that departed in the last 12 hours (to catch up on cron misses) but not older
        $departedServices = TransportService::where('status', 'departed')
            ->where(function ($q) {
                $q->where('departure_date', '=', now()->toDateString())
                  ->where('departure_time', '<=', now()->toTimeString())
                  ->orWhere('departure_date', '<', now()->toDateString());
            })
            ->whereRaw("CONCAT(departure_date, ' ', departure_time) >= ?", [now()->subHours(12)])
            ->get();

        $count = 0;
        foreach ($departedServices as $service) {
            // Find all loaded consignments on this service
            $consignments = $service->consignments()->where('status', 'loaded')->get();
            
            foreach ($consignments as $consignment) {
                $trackingService->logEvent(
                    $consignment,
                    'in_transit',
                    'Vessel departed. Cargo is now in transit.',
                    null
                );
                $count++;
            }
        }

        $this->info("Moved {$count} consignments to in_transit.");
    }
}
