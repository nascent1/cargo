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
        Schema::table('seat_inventory_allocations', function (Blueprint $table) {
            $table->boolean('can_view_increased')->default(true)->after('allocated_seats')
                  ->comment('Controls whether this agent can see/search increased seat capacity');
            $table->unsignedInteger('max_sell_count')->nullable()->after('can_view_increased')
                  ->comment('Maximum number of increased seats this agent can sell. NULL = unlimited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seat_inventory_allocations', function (Blueprint $table) {
            $table->dropColumn(['can_view_increased', 'max_sell_count']);
        });
    }
};
