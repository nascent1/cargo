<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-time cargo capacity tracking per transport service.
     * Multi-dimensional: lane-meters, weight, volume, vehicle slots, compartment slots.
     * Tracks: total, used, held (locks), and blocked.
     */
    public function up(): void
    {
        Schema::create('cargo_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_service_id')->unique()->constrained('transport_services');
            $table->foreignId('transport_unit_id')->constrained('transport_units');
            // Lane-meters (vehicles — ferries)
            $table->decimal('total_lane_meters', 8, 2)->default(0);
            $table->decimal('used_lane_meters', 8, 2)->default(0);
            $table->decimal('held_lane_meters', 8, 2)->default(0);
            $table->decimal('blocked_lane_meters', 8, 2)->default(0);
            // Vehicle slots
            $table->integer('total_vehicle_slots')->default(0);
            $table->integer('used_vehicle_slots')->default(0);
            $table->integer('held_vehicle_slots')->default(0);
            // Weight (freight/parcels)
            $table->decimal('total_weight_capacity_kg', 10, 2)->default(0);
            $table->decimal('used_weight_kg', 10, 2)->default(0);
            $table->decimal('held_weight_kg', 10, 2)->default(0);
            // Volume / CBM (electronics, furniture)
            $table->decimal('total_volume_capacity_cbm', 8, 2)->default(0);
            $table->decimal('used_volume_cbm', 8, 2)->default(0);
            $table->decimal('held_volume_cbm', 8, 2)->default(0);
            // Compartment slots (buses)
            $table->integer('total_compartment_slots')->default(0);
            $table->integer('used_compartment_slots')->default(0);
            $table->integer('held_compartment_slots')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_inventories');
    }
};
