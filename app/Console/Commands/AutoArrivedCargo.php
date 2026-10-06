<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransportService;
use App\Services\Cargo\CargoTrackingService;

class AutoArrivedCargo extends Command
{
    protected $signature = 'cargo:auto-arrived';
    protected $description = 'Automatically move in_transit consignments to delivered (arrived at destination port) when vessel arrives';

    public function handle(CargoTrackingService $trackingService)
    {
        // Find transport services that arrived in the last 24 hours
        // Note: Voyage/TransportService status must be updated by another process/cron
        $arrivedServices = TransportService::where('status', 'arrived')
            ->whereRaw("CONCAT(departure_date, ' ', departure_time) >= ?", [now()->subDays(3)]) // Lookback window
            ->get();

        $count = 0;
        foreach ($arrivedServices as $service) {
            $consignments = $service->consignments()->where('status', 'in_transit')->get();
            
            foreach ($consignments as $consignment) {
                $trackingService->logEvent(
                    $consignment,
                    'delivered', // Delivered to destination port, not yet collected
                    'Vessel arrived at destination port. Cargo ready for collection.',
                    null
                );
                $count++;
            }
        }

        $this->info("Moved {$count} consignments to delivered (arrived at port).");
    }
}
