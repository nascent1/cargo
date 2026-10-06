<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo items — individual pieces of cargo within a consignment.
     * Each has its own dimensions, weight, type, fare, QR code, and chain of custody.
     */
    public function up(): void
    {
        Schema::create('cargo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained('consignments')->cascadeOnDelete();
            $table->string('item_number', 60)->unique();
            $table->foreignId('cargo_type_id')->constrained('cargo_types');
            // Physical attributes
            $table->string('description', 255)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->decimal('length_m', 6, 2)->nullable();
            $table->decimal('width_m', 6, 2)->nullable();
            $table->decimal('height_m', 6, 2)->nullable();
            $table->decimal('volume_cbm', 8, 4)->nullable();
            $table->decimal('lane_meters_consumed', 6, 2)->nullable();
            // Vehicle-specific
            $table->string('vehicle_reg_number', 30)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 30)->nullable();
            // Handling
            $table->boolean('is_fragile')->default(false);
            $table->string('hazmat_class', 10)->nullable();
            $table->string('hazmat_un_number', 10)->nullable();
            $table->text('handling_instructions')->nullable();
            $table->string('packaging_type', 30)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            // Pricing
            $table->decimal('base_fare', 12, 2)->default(0);
            $table->decimal('surcharge_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_fare', 12, 2)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->string('pricing_method', 20)->default('flat');
            $table->foreignId('fare_id')->nullable()->constrained('cargo_fares');
            // Status
            $table->string('status', 30)->default('pending');
            $table->string('qr_code_token', 100)->unique();
            $table->integer('loading_sequence')->nullable();
            $table->string('deck_position', 20)->nullable();
            $table->string('condition_on_receipt', 20)->nullable();
            // Chain of custody
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('loaded_by_user_id')->nullable()->constrained('users');
            $table->timestamp('loaded_at')->nullable();
            $table->foreignId('offloaded_by_user_id')->nullable()->constrained('users');
            $table->timestamp('offloaded_at')->nullable();
            $table->string('collected_by_name', 100)->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->boolean('collection_id_verified')->default(false);
            // Print tracking
            $table->integer('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['consignment_id', 'status'], 'idx_cargo_items_consignment');
            $table->index('vehicle_reg_number', 'idx_cargo_items_vehicle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_items');
    }
};
