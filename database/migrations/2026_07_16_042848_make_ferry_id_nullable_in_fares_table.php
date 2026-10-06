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
        Schema::table('fares', function (Blueprint $table) {
            $table->dropForeign(['ferry_id']);
            $table->dropForeign(['ferry_class_id']);
            
            $table->unsignedBigInteger('ferry_id')->nullable()->change();
            $table->unsignedBigInteger('ferry_class_id')->nullable()->change();
            
            $table->foreign('ferry_id')->references('id')->on('ferries')->cascadeOnDelete();
            $table->foreign('ferry_class_id')->references('id')->on('ferry_classes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fares', function (Blueprint $table) {
            $table->dropForeign(['ferry_id']);
            $table->dropForeign(['ferry_class_id']);
            
            $table->unsignedBigInteger('ferry_id')->nullable(false)->change();
            $table->unsignedBigInteger('ferry_class_id')->nullable(false)->change();
            
            $table->foreign('ferry_id')->references('id')->on('ferries')->cascadeOnDelete();
            $table->foreign('ferry_class_id')->references('id')->on('ferry_classes')->cascadeOnDelete();
        });
    }
};
