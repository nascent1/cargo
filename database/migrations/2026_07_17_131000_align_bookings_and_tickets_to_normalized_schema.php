<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Align bookings table
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'route_id')) {
                $table->foreignId('route_id')->nullable()->after('id')->constrained('routes')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'vessel_id')) {
                $table->foreignId('vessel_id')->nullable()->after('route_id')->constrained('ferries')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'departure_date')) {
                $table->date('departure_date')->nullable()->after('vessel_id');
            }
            if (!Schema::hasColumn('bookings', 'departure_time')) {
                $table->time('departure_time')->nullable()->after('departure_date');
            }
            if (!Schema::hasColumn('bookings', 'contact_name')) {
                $table->string('contact_name', 255)->nullable()->after('booking_reference');
            }
            if (!Schema::hasColumn('bookings', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('bookings', 'contact_email')) {
                $table->string('contact_email', 255)->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('bookings', 'passenger_count')) {
                $table->unsignedInteger('passenger_count')->default(1)->after('contact_email');
            }
            if (!Schema::hasColumn('bookings', 'status_id')) {
                $table->foreignId('status_id')->nullable()->after('status')->constrained('booking_statuses')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'payment_status_id')) {
                $table->foreignId('payment_status_id')->nullable()->after('status_id')->constrained('payment_statuses')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'source_id')) {
                $table->foreignId('source_id')->nullable()->after('booking_source')->constrained('booking_sources')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'booking_agent_id')) {
                $table->foreignId('booking_agent_id')->nullable()->after('booked_by_user_id')->constrained('agents')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('bookings', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
            if (!Schema::hasColumn('bookings', 'is_legacy')) {
                $table->boolean('is_legacy')->default(false)->after('cancellation_reason');
            }
        });

        // 2. Align booking_items (tickets) table
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'passenger_name')) {
                $table->string('passenger_name', 255)->nullable()->after('passenger_id');
            }
            if (!Schema::hasColumn('tickets', 'passenger_type')) {
                $table->string('passenger_type', 30)->default('adult')->after('passenger_name'); // adult, child, infant
            }
            if (!Schema::hasColumn('tickets', 'gender')) {
                $table->string('gender', 20)->nullable()->after('passenger_type'); // male, female
            }
            if (!Schema::hasColumn('tickets', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->after('gender')->constrained('nationalities')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('nationality_id')->constrained('countries')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'identity_type_id')) {
                $table->foreignId('identity_type_id')->nullable()->after('country_id')->constrained('id_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'identity_number')) {
                $table->string('identity_number', 255)->nullable()->after('identity_type_id');
            }
            if (!Schema::hasColumn('tickets', 'currency_code')) {
                $table->string('currency_code', 10)->default('TZS')->after('final_fare');
            }
            if (!Schema::hasColumn('tickets', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 6)->default(1.000000)->after('currency_code');
            }
            if (!Schema::hasColumn('tickets', 'is_complimentary')) {
                $table->boolean('is_complimentary')->default(false)->after('exchange_rate');
            }
            if (!Schema::hasColumn('tickets', 'complimentary_reason')) {
                $table->string('complimentary_reason', 255)->nullable()->after('is_complimentary');
            }
            if (!Schema::hasColumn('tickets', 'status_id')) {
                $table->foreignId('status_id')->nullable()->after('status')->constrained('ticket_statuses')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('cancellation_fee_amount');
            }
            if (!Schema::hasColumn('tickets', 'boarded_at')) {
                $table->timestamp('boarded_at')->nullable()->after('checked_in_at');
            }
            if (!Schema::hasColumn('tickets', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('boarded_at');
            }
            if (!Schema::hasColumn('tickets', 'cancelled_by_type')) {
                $table->string('cancelled_by_type', 255)->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('tickets', 'cancelled_by_id')) {
                $table->unsignedBigInteger('cancelled_by_id')->nullable()->after('cancelled_by_type');
            }
            if (!Schema::hasColumn('tickets', 'qr_code_data')) {
                $table->text('qr_code_data')->nullable()->after('qr_code_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['nationality_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['identity_type_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn([
                'passenger_name', 'passenger_type', 'gender', 'nationality_id', 'country_id',
                'identity_type_id', 'identity_number', 'currency_code', 'exchange_rate',
                'is_complimentary', 'complimentary_reason', 'status_id', 'checked_in_at',
                'boarded_at', 'cancelled_at', 'cancelled_by_type', 'cancelled_by_id', 'qr_code_data'
            ]);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropForeign(['vessel_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['payment_status_id']);
            $table->dropForeign(['source_id']);
            $table->dropForeign(['booking_agent_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'route_id', 'vessel_id', 'departure_date', 'departure_time', 'contact_name',
                'contact_phone', 'contact_email', 'passenger_count', 'status_id', 'payment_status_id',
                'source_id', 'booking_agent_id', 'completed_at', 'cancelled_at', 'cancelled_by',
                'cancellation_reason', 'is_legacy'
            ]);
        });
    }
};
