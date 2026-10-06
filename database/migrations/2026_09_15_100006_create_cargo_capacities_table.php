<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo deck/hold specifications per transport unit.
     * Multi-dimensional capacity: lane-meters, weight, volume, vehicle slots, compartment slots.
     */
    public function up(): void
    {
        Schema::create('cargo_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_unit_id')->unique()->constrained('transport_units');
            $table->decimal('total_lane_meters', 8, 2)->default(0);
            $table->integer('max_vehicle_count')->default(0);
            $table->decimal('total_weight_capacity_kg', 10, 2)->default(0);
            $table->decimal('total_volume_capacity_cbm', 8, 2)->default(0);
            $table->integer('compartment_slots')->default(0);       // buses
            $table->decimal('max_payload_tons', 8, 2)->default(0);  // trucks
            $table->integer('deck_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_capacities');
    }
};
