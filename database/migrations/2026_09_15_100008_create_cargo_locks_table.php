<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two-phase pessimistic capacity lock.
     * Created in Phase 1 (hold), consumed in Phase 2 (store).
     * Prevents TOCTOU overbooking during agent data entry window.
     */
    public function up(): void
    {
        Schema::create('cargo_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_service_id')->constrained('transport_services');
            $table->foreignId('cargo_type_id')->nullable()->constrained('cargo_types');
            $table->decimal('lane_meters', 6, 2)->default(0);
            $table->decimal('weight_kg', 10, 2)->default(0);
            $table->decimal('volume_cbm', 8, 2)->default(0);
            $table->integer('vehicle_slots')->default(0);
            $table->integer('compartment_slots')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('session_id', 100)->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents');
            $table->decimal('amount_held', 12, 2)->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['transport_service_id', 'expires_at'], 'idx_cargo_locks_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_locks');
    }
};
