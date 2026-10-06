<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Payment Gateway Logs — raw request/callback/query payloads from gateways
        if (!Schema::hasTable('payment_gateway_logs')) {
            Schema::create('payment_gateway_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->string('gateway', 50); // selcom, tigo, mpesa, stripe, cash, mock
                $table->string('event_type', 50); // initiate, callback, query, refund, webhook
                $table->string('direction', 10)->default('outgoing'); // outgoing, incoming
                $table->string('http_method', 10)->nullable(); // POST, GET
                $table->text('url')->nullable();
                $table->json('request_headers')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->unsignedSmallInteger('http_status_code')->nullable();
                $table->string('transaction_reference')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('currency', 3)->nullable();
                $table->boolean('is_success')->default(false);
                $table->text('error_message')->nullable();
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->timestamps();

                $table->index(['gateway', 'event_type']);
                $table->index('transaction_reference');
                $table->index('created_at');
            });
        }

        // 2. Receipts table — payment receipt tracking
        if (!Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('TZS');
                $table->string('payment_method', 50)->nullable();
                $table->string('status', 20)->default('issued'); // issued, void, reissued
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->unsignedInteger('print_count')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('booking_id');
                $table->index('issued_at');
            });
        }

        // 3. Port Operators table — gate staff management
        if (!Schema::hasTable('port_operators')) {
            Schema::create('port_operators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('port_id')->constrained('ports')->cascadeOnDelete();
                $table->string('operator_number', 50)->unique();
                $table->string('badge_number', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'port_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('port_operators');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_gateway_logs');
    }
};
