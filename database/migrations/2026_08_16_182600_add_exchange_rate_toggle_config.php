<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add exchange rate conversion toggle (enabled by default — preserves existing behavior)
        DB::table('system_configs')->insertOrIgnore([
            'key_name'    => 'enable_exchange_rate_conversion',
            'value'       => '1',
            'description' => 'Enable/disable USD→TZS exchange rate conversion for non-resident bookings. When disabled, USD fares are kept as-is without conversion.',
            'group'       => 'payment',
            'is_sensitive' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_configs')->where('key_name', 'enable_exchange_rate_conversion')->delete();
    }
};
