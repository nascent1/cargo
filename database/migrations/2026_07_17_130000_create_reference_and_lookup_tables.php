<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reference Data: Countries
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('code', 3)->unique(); // ISO 3166-1 alpha-2 / alpha-3
                $table->string('phone_code', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Reference Data: Nationalities
        if (!Schema::hasTable('nationalities')) {
            Schema::create('nationalities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('code', 3)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Reference Data: Currencies
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 3)->unique(); // TZS, USD, KES, EUR
                $table->string('name', 100);
                $table->string('symbol', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 4. Reference Data: Identity Types (Align with ferry_bookings_db.sql identity_types)
        if (!Schema::hasTable('identity_types')) {
            Schema::create('identity_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Reference Data: Exchange Rates
        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('from_currency', 3);
                $table->string('to_currency', 3);
                $table->decimal('rate', 12, 6);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['from_currency', 'to_currency', 'effective_from']);
            });
        }

        // 6. Lookup Tables: Booking Statuses
        if (!Schema::hasTable('booking_statuses')) {
            Schema::create('booking_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->string('color', 50)->default('slate'); // green, amber, red, blue, slate
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 7. Lookup Tables: Booking Sources
        if (!Schema::hasTable('booking_sources')) {
            Schema::create('booking_sources', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 8. Lookup Tables: Ticket Statuses
        if (!Schema::hasTable('ticket_statuses')) {
            Schema::create('ticket_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->string('color', 50)->default('slate');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 9. Lookup Tables: Payment Statuses
        if (!Schema::hasTable('payment_statuses')) {
            Schema::create('payment_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->string('color', 50)->default('slate');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 10. Lookup Tables: Payment Methods
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 11. Locations Table
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 20)->unique();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 12. Vessel Certificates Table
        if (!Schema::hasTable('ferry_certificates')) {
            Schema::create('ferry_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_id')->constrained('ferries')->cascadeOnDelete();
                $table->string('certificate_number', 100)->unique();
                $table->string('certificate_type', 100); // Seaworthiness, Passenger Safety, Insurance, Radio License
                $table->string('issued_by', 100)->nullable(); // e.g. SUMATRA / ZMA
                $table->date('issued_date');
                $table->date('expiry_date');
                $table->unsignedInteger('max_passengers')->nullable();
                $table->unsignedInteger('max_crew')->nullable();
                $table->string('status', 30)->default('valid'); // valid, expired, suspended
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['vessel_id', 'status']);
                $table->index('expiry_date');
            });
        }

        // 13. Seat Inventories Table
        if (!Schema::hasTable('seat_inventories')) {
            Schema::create('seat_inventories', function (Blueprint $table) {
                $table->id();
                $table->date('departure_date');
                $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
                $table->foreignId('vessel_id')->constrained('ferries')->cascadeOnDelete();
                $table->foreignId('travel_class_id')->constrained('ferry_classes')->cascadeOnDelete();
                $table->unsignedInteger('total_seats')->default(0);
                $table->unsignedInteger('sold_seats')->default(0);
                $table->unsignedInteger('held_seats')->default(0);
                $table->unsignedInteger('blocked_seats')->default(0);
                $table->timestamps();

                $table->unique(['departure_date', 'route_id', 'vessel_id', 'travel_class_id'], 'seat_inv_unique');
            });
        }

        // 14. Queue Infrastructure Tables
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('seat_inventories');
        Schema::dropIfExists('ferry_certificates');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('ticket_statuses');
        Schema::dropIfExists('booking_sources');
        Schema::dropIfExists('booking_statuses');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('identity_types');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('nationalities');
        Schema::dropIfExists('countries');
    }
};
