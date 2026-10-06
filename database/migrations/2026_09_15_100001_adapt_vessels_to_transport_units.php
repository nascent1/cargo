<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 0 cleanup: Drop passenger-specific tables that have FK constraints
     * on ferries/voyages before we can rename them.
     * Then adapt ferries → transport_units for multi-modal cargo support.
     */
    public function up(): void
    {
        // 1. Drop passenger-specific tables using CASCADE to handle all FK dependencies
        $tables = [
            'boarding_events', 'booking_locks', 'complimentary_tickets',
            'pending_refunds', 'booking_logs', 'tickets',
            'seat_inventory_allocations', 'seat_inventories',
            'bookings', 'ferry_class_rooms', 'ferry_classes',
            'ferry_certificates', 'route_prices', 'price_discounts',
            'report_snapshots', 'portal_translations', 'agent_vessel_access',
            'coupons', 'coupon_usages', 'shifts', 'shift_overtime_requests',
            'booking_sources', 'booking_statuses', 'ticket_statuses',
            'travel_classes', 'passenger_types', 'corporate_invoices',
            'corporates', 'announcements', 'scanners',
        ];
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS \"{$table}\" CASCADE");
        }

        // 2. Rename ferries → transport_units
        Schema::rename('ferries', 'transport_units');

        // 3. Add multi-modal columns
        Schema::table('transport_units', function (Blueprint $table) {
            $table->string('transport_type', 20)->default('ferry')->after('name');
            $table->boolean('has_cargo_deck')->default(true)->after('status');
            $table->string('registration_number', 30)->nullable()->after('has_cargo_deck');
            $table->jsonb('metadata')->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('transport_units', function (Blueprint $table) {
            $table->dropColumn(['transport_type', 'has_cargo_deck', 'registration_number', 'metadata']);
        });

        Schema::rename('transport_units', 'ferries');
    }
};
