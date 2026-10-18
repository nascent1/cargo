<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each booked line keeps its own copy of how it was priced, so reports and audits never depend on
 * the fare row (which can later be ended or revoked).
 *
 * unit_rate        — rate charged per kg / cbm / piece
 * value_charge_pct — % of declared value charged
 * min_charge       — minimum charge in force (pricing_method = 'minimum' when it applied)
 * priced_at        — the booking moment the fare was chosen for (when space was held)
 * fare_version     — version of fare_id used
 * pricing_snapshot — the full record: fare window, type, route, rates, surcharge lines and settings
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargo_items', function (Blueprint $table) {
            $table->decimal('unit_rate', 12, 4)->nullable()->after('pricing_method');
            $table->decimal('value_charge_pct', 5, 2)->default(0)->after('value_charge');
            $table->decimal('min_charge', 12, 2)->nullable()->after('unit_rate');
            $table->timestamp('priced_at')->nullable()->after('min_charge');
            $table->unsignedSmallInteger('fare_version')->nullable()->after('fare_id');
            $table->jsonb('pricing_snapshot')->nullable()->after('priced_at');
        });

        // Lines booked before snapshots existed: derive the rate from the amounts they were charged
        DB::statement(<<<'SQL'
            UPDATE cargo_items SET
                unit_rate = CASE pricing_method
                    WHEN 'per_kg'  THEN base_fare / NULLIF(weight_kg, 0)
                    WHEN 'per_cbm' THEN base_fare / NULLIF(volume_cbm, 0)
                    ELSE COALESCE(NULLIF(base_fare, 0), final_fare) / GREATEST(quantity, 1)
                END,
                value_charge_pct = CASE WHEN COALESCE(declared_value, 0) > 0 THEN ROUND(value_charge * 100 / declared_value, 2) ELSE 0 END,
                priced_at = created_at,
                fare_version = CASE WHEN fare_id IS NOT NULL THEN 1 END,
                pricing_snapshot = jsonb_build_object(
                    'source', 'backfill',
                    'fare', jsonb_build_object('id', fare_id, 'version', CASE WHEN fare_id IS NOT NULL THEN 1 END),
                    'note', 'Booked before price snapshots existed; the rate is derived from the amounts charged.'
                )
            WHERE pricing_snapshot IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('cargo_items', function (Blueprint $table) {
            $table->dropColumn(['unit_rate', 'value_charge_pct', 'min_charge', 'priced_at', 'fare_version', 'pricing_snapshot']);
        });
    }
};
