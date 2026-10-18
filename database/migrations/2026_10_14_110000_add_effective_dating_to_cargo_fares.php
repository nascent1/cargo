<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cargo fares become versions with a validity window instead of rows edited in place.
 *
 * effective_from / effective_until — the window the fare prices bookings in: from included, until excluded,
 *                                    until null = no end. Bookings are priced by the time space is held.
 * previous_fare_id / version       — the edit chain (an edit closes the old version and adds a new one)
 * status                           — active, cancelled (replaced or withdrawn before it started),
 *                                    revoked (configured incorrectly), inactive (old switch-off)
 *
 * Two active fares for the same cargo type + route + vessel can never overlap in time: the database
 * refuses it (exclusion constraint), which also closes the old unique index's NULL-vessel loophole.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->timestamp('effective_from')->nullable()->after('status');
            $table->timestamp('effective_until')->nullable()->after('effective_from');
            $table->foreignId('previous_fare_id')->nullable()->after('effective_until')->constrained('cargo_fares');
            $table->unsignedSmallInteger('version')->default(1)->after('previous_fare_id');
            $table->string('change_reason', 255)->nullable()->after('version');
            $table->foreignId('created_by')->nullable()->after('change_reason')->constrained('users');
            $table->foreignId('ended_by')->nullable()->after('created_by')->constrained('users');
            $table->timestamp('ended_at')->nullable()->after('ended_by');
            $table->foreignId('dual_control_request_id')->nullable()->after('ended_at')->constrained('dual_control_requests');
        });

        // Existing fares have applied since they were created
        DB::statement('UPDATE cargo_fares SET effective_from = created_at WHERE effective_from IS NULL');
        DB::statement('UPDATE cargo_fares SET effective_from = NOW() WHERE effective_from IS NULL');
        DB::statement('ALTER TABLE cargo_fares ALTER COLUMN effective_from SET NOT NULL');
        DB::statement('ALTER TABLE cargo_fares ALTER COLUMN effective_from SET DEFAULT CURRENT_TIMESTAMP(0)');

        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->dropUnique('cargo_fares_route_unit_type_unique');
            $table->index(['cargo_type_id', 'route_id', 'effective_from'], 'cargo_fares_type_route_from_index');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(<<<'SQL'
            ALTER TABLE cargo_fares ADD CONSTRAINT cargo_fares_no_overlap EXCLUDE USING gist (
                cargo_type_id WITH =,
                route_id WITH =,
                (COALESCE(transport_unit_id, 0)) WITH =,
                tsrange(effective_from, effective_until, '[)') WITH &&
            ) WHERE (deleted_at IS NULL AND status = 'active')
        SQL);
        DB::statement("ALTER TABLE cargo_fares ADD CONSTRAINT cargo_fares_window_check CHECK (effective_until IS NULL OR effective_until > effective_from)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cargo_fares DROP CONSTRAINT IF EXISTS cargo_fares_no_overlap');
        DB::statement('ALTER TABLE cargo_fares DROP CONSTRAINT IF EXISTS cargo_fares_window_check');

        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->dropIndex('cargo_fares_type_route_from_index');
            $table->dropConstrainedForeignId('dual_control_request_id');
            $table->dropConstrainedForeignId('ended_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('previous_fare_id');
            $table->dropColumn(['effective_from', 'effective_until', 'version', 'change_reason', 'ended_at']);
        });

        // Only possible while each type + route + vessel has a single row; otherwise restore the pre-migration dump
        Schema::table('cargo_fares', function (Blueprint $table) {
            $table->unique(['route_id', 'transport_unit_id', 'cargo_type_id'], 'cargo_fares_route_unit_type_unique');
        });
    }
};
