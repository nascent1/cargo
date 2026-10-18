<?php

use App\Models\DualControlConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Register every operation in config/dual_control.php as a rule and re-file existing rules by module.
 * Rules that already exist keep their on/off setting. Pending requests already past their expiry
 * (request #1, stuck since 20 Sep 2026) are marked expired.
 */
return new class extends Migration
{
    public function up(): void
    {
        DualControlConfig::syncFromRegistry();

        DB::table('dual_control_requests')
            ->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'resolved_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Remove the rules this migration registered (the original rules date from the September seed).
        // Module re-filing and expired requests are not reversed; restore the pre-migration dump for that.
        DB::table('dual_control_configs')->where('created_at', '>=', '2026-10-14 00:00:00')->delete();
    }
};
