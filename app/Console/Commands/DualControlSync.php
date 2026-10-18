<?php

namespace App\Console\Commands;

use App\Models\DualControlConfig;
use Illuminate\Console\Command;

class DualControlSync extends Command
{
    protected $signature = 'dual-control:sync';
    protected $description = 'Register every operation in config/dual_control.php as a dual-control rule (existing on/off settings are kept)';

    public function handle(): int
    {
        $result = DualControlConfig::syncFromRegistry();
        $this->info("Dual-control rules: {$result['created']} registered, {$result['updated']} re-filed.");

        return self::SUCCESS;
    }
}
