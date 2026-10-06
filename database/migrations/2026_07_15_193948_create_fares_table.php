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
        Schema::create('fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->string('class_name', 50); // e.g., 'Economy', 'VIP'
            $table->string('passenger_category', 50); // e.g., 'adult_resident', 'child_resident'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TZS');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['route_id', 'class_name', 'passenger_category'], 'fare_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fares');
    }
};
