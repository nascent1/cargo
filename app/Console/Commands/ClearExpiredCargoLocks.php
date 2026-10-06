<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CargoLock;
use App\Services\Cargo\CargoInventoryService;
use Illuminate\Support\Facades\Log;

class ClearExpiredCargoLocks extends Command
{
    protected $signature = 'cargo:clear-locks';
    protected $description = 'Clear expired cargo inventory locks to free up capacity';

    public function handle(CargoInventoryService $inventoryService)
    {
        $expiredLocks = CargoLock::where('expires_at', '<', now())->get();
        
        $count = 0;
        foreach ($expiredLocks as $lock) {
            try {
                $inventoryService->releaseLock($lock);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to release expired cargo lock {$lock->id}: " . $e->getMessage());
            }
        }

        if ($count > 0) {
            $this->info("Cleared {$count} expired cargo locks.");
        } else {
            $this->info("No expired cargo locks found.");
        }
    }
}
