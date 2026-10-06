<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SystemConfig;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['price_discounts', 'promotions', 'coupons'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('target_audience', ['all', 'residents', 'foreigners'])->default('all')->after('discount_value');
                $table->decimal('discount_value_usd', 15, 2)->nullable()->after('target_audience');
            });
        }

        // Backfill existing fixed_amount discounts with USD equivalent
        $rate = (float) SystemConfig::getValue('usd_to_tzs_exchange_rate', 2600.00);
        if ($rate <= 0) $rate = 2600.00;

        foreach ($tables as $table) {
            DB::table($table)->where('discount_type', 'fixed_amount')->orderBy('id')->chunk(100, function ($records) use ($table, $rate) {
                foreach ($records as $record) {
                    $usdValue = round(((float)$record->discount_value) / $rate, 2);
                    DB::table($table)->where('id', $record->id)->update([
                        'discount_value_usd' => $usdValue
                    ]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts_tables', function (Blueprint $table) {
            //
        });
    }
};
