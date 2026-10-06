<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('booking_source');
            }
            if (!Schema::hasColumn('bookings', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('payment_channel');
            }
            if (!Schema::hasColumn('bookings', 'completed_by_user_id')) {
                $table->foreignId('completed_by_user_id')->nullable()->after('booked_by_user_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'print_count')) {
                $table->unsignedInteger('print_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('tickets', 'last_printed_at')) {
                $table->timestamp('last_printed_at')->nullable()->after('print_count');
            }
            if (!Schema::hasColumn('tickets', 'cancellation_fee_amount')) {
                $table->decimal('cancellation_fee_amount', 12, 2)->default(0.00)->after('final_fare');
            }
        });

        if (!Schema::hasTable('booking_logs')) {
            Schema::create('booking_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
                $table->string('action'); // e.g., 'Booking Created', 'Payment Completed', 'Ticket Cancelled', 'Booking Cancelled', 'Edit Booking', 'Print Ticket', 'Ticket Rescheduled'
                $table->text('description')->nullable();
                $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_logs');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'last_printed_at', 'cancellation_fee_amount']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['completed_by_user_id']);
            $table->dropColumn(['payment_channel', 'transaction_reference', 'completed_by_user_id']);
        });
    }
};
