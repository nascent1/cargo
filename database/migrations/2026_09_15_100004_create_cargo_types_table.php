<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master cargo type taxonomy.
     * Categories: vehicle, freight, parcel, electronics, livestock, special.
     * Measurement units: lane_meter, kg, cbm, unit.
     */
    public function up(): void
    {
        Schema::create('cargo_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('category', 30);           // vehicle|freight|parcel|electronics|livestock|special
            $table->string('measurement_unit', 20);    // lane_meter|kg|cbm|unit
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_length_m', 6, 2)->nullable();
            $table->decimal('max_height_m', 6, 2)->nullable();
            $table->decimal('max_width_m', 6, 2)->nullable();
            $table->decimal('lane_meters', 6, 2)->default(0);
            $table->boolean('requires_hazmat_declaration')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_types');
    }
};
