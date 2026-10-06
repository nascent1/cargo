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
        Schema::create('ferry_class_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ferry_class_id')->constrained('ferry_classes')->onDelete('cascade');
            $table->string('name');
            $table->integer('capacity');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_class_rooms');
    }
};
