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
        Schema::table('booking_locks', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->after('ferry_class_id');
            $table->boolean('is_whole_room')->default(false)->after('room_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->after('ferry_class_id');
            $table->boolean('is_whole_room')->default(false)->after('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_locks', function (Blueprint $table) {
            $table->dropColumn(['room_id', 'is_whole_room']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['room_id', 'is_whole_room']);
        });
    }
};
