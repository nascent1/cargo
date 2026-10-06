<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo fares — pricing per cargo type per route.
     * Supports flat, per-kg, per-CBM pricing with minimum charge floors.
     */
    public function up(): void
    {
        Schema::create('cargo_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('transport_unit_id')->nullable()->constrained('transport_units');
            $table->foreignId('cargo_type_id')->constrained('cargo_types');
            // Flat pricing (vehicles, livestock)
            $table->decimal('price_tzs', 12, 2)->default(0);
            $table->decimal('price_usd', 12, 2)->default(0);
            // Per-kg pricing (freight, parcels)
            $table->decimal('price_per_kg_tzs', 10, 4)->nullable();
            $table->decimal('price_per_kg_usd', 10, 4)->nullable();
            // Per-CBM pricing (electronics, furniture)
            $table->decimal('price_per_cbm_tzs', 10, 4)->nullable();
            $table->decimal('price_per_cbm_usd', 10, 4)->nullable();
            // Minimum charge floor
            $table->decimal('min_charge_tzs', 12, 2)->nullable();
            $table->decimal('min_charge_usd', 12, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['route_id', 'transport_unit_id', 'cargo_type_id'], 'cargo_fares_route_unit_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_fares');
    }
};
