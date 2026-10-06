<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adapt voyages → transport_services for multi-modal cargo.
     * Renames ferry_id → transport_unit_id.
     */
    public function up(): void
    {
        // Drop the FK constraint first, then rename
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropForeign(['ferry_id']);
        });

        // Drop the check constraint on status
        DB::statement("ALTER TABLE voyages DROP CONSTRAINT IF EXISTS voyages_status_check");

        Schema::rename('voyages', 'transport_services');

        Schema::table('transport_services', function (Blueprint $table) {
            $table->renameColumn('ferry_id', 'transport_unit_id');
        });

        Schema::table('transport_services', function (Blueprint $table) {
            $table->string('transport_type', 20)->default('ferry')->after('id');
            $table->string('service_number', 50)->nullable()->after('transport_type');
            $table->timestamp('actual_departure_at')->nullable();
            $table->timestamp('actual_arrival_at')->nullable();

            // Re-create FK to transport_units
            $table->foreign('transport_unit_id')->references('id')->on('transport_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_services', function (Blueprint $table) {
            $table->dropForeign(['transport_unit_id']);
            $table->dropColumn(['transport_type', 'service_number', 'actual_departure_at', 'actual_arrival_at']);
        });

        Schema::table('transport_services', function (Blueprint $table) {
            $table->renameColumn('transport_unit_id', 'ferry_id');
        });

        Schema::rename('transport_services', 'voyages');

        Schema::table('voyages', function (Blueprint $table) {
            $table->foreign('ferry_id')->references('id')->on('ferries')->cascadeOnDelete();
        });
    }
};
