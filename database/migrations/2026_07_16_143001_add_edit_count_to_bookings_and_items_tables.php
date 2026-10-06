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
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('edit_count')->default(0)->after('notes');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('edit_count')->default(0)->after('print_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('edit_count');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('edit_count');
        });
    }
};
