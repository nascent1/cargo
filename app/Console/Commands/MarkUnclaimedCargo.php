<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consignment;
use App\Models\SystemConfig;
use App\Services\Cargo\CargoTrackingService;

class MarkUnclaimedCargo extends Command
{
    protected $signature = 'cargo:mark-unclaimed';
    protected $description = 'Mark delivered cargo as unclaimed if not collected within configured days';

    public function handle(CargoTrackingService $trackingService)
    {
        // Default to 14 days if not configured
        $daysConfig = SystemConfig::where('key_name', 'cargo_unclaimed_days')->first();
        $days = $daysConfig ? (int) $daysConfig->value : 14;

        $threshold = now()->subDays($days);

        $unclaimedConsignments = Consignment::where('status', 'delivered')
            ->where('delivered_at', '<', $threshold)
            ->get();
            
        $count = 0;
        foreach ($unclaimedConsignments as $consignment) {
            $trackingService->logEvent(
                $consignment, 
                'unclaimed', 
                "Marked as unclaimed after {$days} days", 
                null, 
                null
            );
            $count++;
        }

        $this->info("Marked {$count} consignments as unclaimed.");
    }
}
