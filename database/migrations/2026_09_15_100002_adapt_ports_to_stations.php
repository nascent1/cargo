<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adapt ports to generic stations (ports, bus stations, depots, warehouses).
     */
    public function up(): void
    {
        Schema::rename('ports', 'stations');

        Schema::table('stations', function (Blueprint $table) {
            $table->string('station_type', 30)->default('port')->after('name');
            $table->decimal('latitude', 10, 7)->nullable()->after('station_type');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['station_type', 'latitude', 'longitude']);
        });

        Schema::rename('stations', 'ports');
    }
};
