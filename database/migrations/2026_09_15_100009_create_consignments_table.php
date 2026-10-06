<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consignments — the core aggregate root of the cargo domain.
     * Groups sender, receiver, and cargo items under a single booking.
     * Replaces the passenger 'bookings' table concept.
     */
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('consignment_number', 60)->unique();
            $table->string('tracking_number', 20)->unique();
            // Transport
            $table->foreignId('transport_service_id')->constrained('transport_services');
            $table->foreignId('route_id')->constrained('routes');
            // Sender details
            $table->string('sender_name', 100);
            $table->string('sender_phone', 30)->nullable();
            $table->string('sender_email', 100)->nullable();
            $table->string('sender_id_type', 30)->nullable();
            $table->string('sender_id_number', 50)->nullable();
            $table->string('sender_address', 255)->nullable();
            // Receiver details
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 30)->nullable();
            $table->string('receiver_email', 100)->nullable();
            $table->string('receiver_id_type', 30)->nullable();
            $table->string('receiver_id_number', 50)->nullable();
            $table->string('receiver_address', 255)->nullable();
            // Financial
            $table->decimal('total_amount_tzs', 12, 2)->default(0);
            $table->decimal('total_amount_usd', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            $table->jsonb('pricing_breakdown')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_status', 20)->default('unpaid');
            // Status & tracking
            $table->string('status', 30)->default('draft');
            $table->string('qr_code_token', 100)->unique();
            // Aggregates
            $table->integer('total_items')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->default(0);
            $table->decimal('total_volume_cbm', 8, 4)->default(0);
            $table->boolean('has_hazmat')->default(false);
            $table->boolean('has_fragile')->default(false);
            $table->boolean('has_livestock')->default(false);
            $table->text('special_instructions')->nullable();
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            // Audit
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users');
            $table->foreignId('booked_by_agent_id')->nullable()->constrained('agents');
            $table->timestamps();
            $table->softDeletes();

            $table->index('transport_service_id', 'idx_consignments_service');
            $table->index('status', 'idx_consignments_status');
            $table->index('sender_phone', 'idx_consignments_sender');
            $table->index('receiver_phone', 'idx_consignments_receiver');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};
