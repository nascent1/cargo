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
        Schema::table('dual_control_configs', function (Blueprint $table) {
            $table->string('module')->default('General')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dual_control_configs', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
