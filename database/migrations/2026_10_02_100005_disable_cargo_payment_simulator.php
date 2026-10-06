<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The push simulator auto-completes payments, which made unpaid bookings look paid.
 * It is now an explicit testing opt-in; channels without credentials leave the
 * booking awaiting payment instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_configs')->where('key_name', 'cargo_payment_simulator_enabled')->update([
            'value'       => '0',
            'description' => 'Testing only (local): push channels without credentials auto-complete payments ~8s later. Never active in production.',
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_configs')->where('key_name', 'cargo_payment_simulator_enabled')->update([
            'value'       => '1',
            'description' => 'Local/dev only: simulate unconfigured USSD push channels (never active in production)',
            'updated_at'  => now(),
        ]);
    }
};
