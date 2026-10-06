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
        Schema::table('ferries', function (Blueprint $table) {
            $table->string('layout_image_path')->nullable()->after('has_layout');
            $table->json('seat_layout_json')->nullable()->after('layout_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferries', function (Blueprint $table) {
            $table->dropColumn(['layout_image_path', 'seat_layout_json']);
        });
    }
};
