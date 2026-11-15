<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0 of the parcel and contract-cargo programme: the pieces every later phase uses.
 * Nothing here changes how booking works today.
 */
return new class extends Migration
{
    // Service-line switches (AD-11). Both off: each line is switched on when its phase is accepted.
    private const SWITCHES = [
        ['parcel_receive_flow_enabled', 'Parcel service: receive at the counter, assign to a tray on a sailing, board, collect'],
        ['contract_cargo_enabled', 'Contract cargo: customers, quotations, contracts, invoices and charter voyages'],
    ];

    public function up(): void
    {
        // Duty station: where a staff member works. Empty until an administrator sets it.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('domain')->constrained('stations')->nullOnDelete();
        });

        Schema::table('stations', function (Blueprint $table) {
            $table->boolean('has_parcel_office')->default(true)->after('station_type');
        });

        // Every existing consignment is a parcel; contract cargo arrives in Phase 5.
        Schema::table('consignments', function (Blueprint $table) {
            $table->string('service_type', 20)->default('parcel')->after('status')->index();
        });

        // One row per numbered series and year. The row is locked while a number is taken.
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('series', 40);
            $table->unsignedSmallInteger('year');
            $table->string('prefix', 20);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->timestamps();

            $table->unique(['series', 'year']);
        });

        // Files attached to any record: signed quotations, proof of delivery, damage photos.
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('kind', 40);
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        $now = now();
        foreach (self::SWITCHES as [$key, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => '0', 'description' => $description,
                'is_sensitive' => false, 'group' => 'cargo', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_configs')->whereIn('key_name', array_column(self::SWITCHES, 0))->delete();

        Schema::dropIfExists('attachments');
        Schema::dropIfExists('document_sequences');

        Schema::table('consignments', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });

        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('has_parcel_office');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });
    }
};
