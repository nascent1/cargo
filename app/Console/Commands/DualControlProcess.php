<?php

namespace App\Console\Commands;

use App\Services\DualControlService;
use Illuminate\Console\Command;

class DualControlProcess extends Command
{
    protected $signature = 'dual-control:process';
    protected $description = 'Apply approved changes whose start time has come and expire requests nobody approved in time';

    public function handle(DualControlService $dualControl): int
    {
        $result = $dualControl->processDue();

        if (array_sum($result) > 0) {
            $this->info("Dual control: {$result['applied']} applied, {$result['failed']} failed, {$result['expired']} expired.");
        }

        return self::SUCCESS;
    }
}
