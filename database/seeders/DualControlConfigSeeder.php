<?php

namespace Database\Seeders;

use App\Models\DualControlConfig;
use Illuminate\Database\Seeder;

class DualControlConfigSeeder extends Seeder
{
    /**
     * Register every operation listed in config/dual_control.php. Rules that already exist keep
     * their on/off setting (re-seeding never switches approval on or off behind anyone's back).
     */
    public function run(): void
    {
        DualControlConfig::syncFromRegistry();
    }
}
