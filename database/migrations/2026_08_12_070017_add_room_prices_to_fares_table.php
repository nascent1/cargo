<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fares', function (Blueprint $table) {
            $table->decimal('whole_room_price', 10, 2)->nullable()->after('child_price_usd');
            $table->decimal('whole_room_price_usd', 10, 2)->nullable()->after('whole_room_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fares', function (Blueprint $table) {
            $table->dropColumn(['whole_room_price', 'whole_room_price_usd']);
        });
    }
};
