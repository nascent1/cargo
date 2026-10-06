<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('frequency_pattern')->nullable()->after('end_date');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('frequency_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('frequency_pattern')->nullable()->after('arrival_time');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('frequency_pattern');
        });
    }
};
