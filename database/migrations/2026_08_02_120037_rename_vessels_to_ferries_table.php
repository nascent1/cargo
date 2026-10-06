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
        Schema::table('ferry_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('ferry_certificates', 'vessel_id')) {
                $table->renameColumn('vessel_id', 'ferry_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferry_certificates', function (Blueprint $table) {
            $table->renameColumn('ferry_id', 'vessel_id');
        });
        
        Schema::rename('ferry_certificates', 'ferry_certificates');
    }
};
