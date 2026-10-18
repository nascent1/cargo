<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Value charge: a fare may add a percentage of the cargo's declared value to the price.
 * Every fare starts at 0%, so no price changes until a percentage is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->decimal('value_charge_pct', 5, 2)->default(0)->after('min_charge_usd');
        });

        Schema::table('cargo_items', function (Blueprint $table) {
            $table->decimal('value_charge', 12, 2)->default(0)->after('surcharge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cargo_items', function (Blueprint $table) {
            $table->dropColumn('value_charge');
        });

        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->dropColumn('value_charge_pct');
        });
    }
};
