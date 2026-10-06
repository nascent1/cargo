<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracking events — immutable, append-only event log for cargo lifecycle.
     * Every state transition, location update, and exception is recorded here.
     */
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained('consignments');
            $table->foreignId('cargo_item_id')->nullable()->constrained('cargo_items');
            $table->string('event_type', 50);       // state_change|location_update|note|exception
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('location_name', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('users');
            $table->string('performed_by_name', 100)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['consignment_id', 'created_at'], 'idx_tracking_consignment');
            $table->index(['cargo_item_id', 'created_at'], 'idx_tracking_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
