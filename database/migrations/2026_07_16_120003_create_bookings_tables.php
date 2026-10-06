<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 50)->unique(); // e.g. VFF-234235345345
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // passenger/customer owner
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // who booked it: agent, staff, or passenger
            $table->string('booking_source', 30)->default('web'); // web, app, office, agent, pos, api
            $table->string('status', 30)->default('held'); // held, pending_payment, confirmed, cancelled, expired
            $table->string('payment_status', 30)->default('pending'); // pending, paid, failed, refunded
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->timestamp('expires_at')->nullable(); // for 10-minute hold countdown
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_reference', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('ticket_number', 60)->unique(); // e.g. VFF-234235345345-1
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignId('ferry_class_id')->constrained('ferry_classes')->cascadeOnDelete();
            $table->foreignId('passenger_id')->nullable()->constrained('passengers')->nullOnDelete();
            $table->string('seat_number', 20)->nullable(); // e.g. A1
            $table->decimal('base_fare', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_fare', 12, 2)->default(0);
            $table->string('status', 30)->default('held'); // held, confirmed, checked_in, boarded, cancelled, expired
            $table->string('qr_code_token', 100)->unique(); // unique hash for barcode/scanner validation
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'status']);
            $table->index(['voyage_id', 'ferry_class_id']);
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('bookings');
    }
};
