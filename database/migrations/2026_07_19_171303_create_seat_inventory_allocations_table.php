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
        Schema::create('seat_inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_inventory_id')->constrained('seat_inventories')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->unsignedInteger('allocated_seats')->default(0);
            $table->timestamps();

            $table->unique(['seat_inventory_id', 'agent_id'], 'seat_inv_agent_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_inventory_allocations');
    }
};
