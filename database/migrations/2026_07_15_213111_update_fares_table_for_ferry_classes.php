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
        Schema::dropIfExists('fares');
        
        Schema::create('fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ferry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ferry_class_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('adult_price', 10, 2)->default(0);
            $table->decimal('child_price', 10, 2)->default(0);
            $table->decimal('adult_price_usd', 10, 2)->default(0);
            $table->decimal('child_price_usd', 10, 2)->default(0);
            
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fares', function (Blueprint $table) {
            $table->string('class_name');
            $table->string('passenger_category');
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            
            $table->dropForeign(['ferry_id']);
            $table->dropForeign(['ferry_class_id']);
            $table->dropColumn(['ferry_id', 'ferry_class_id', 'adult_price', 'child_price', 'adult_price_usd', 'child_price_usd', 'status']);
        });
    }
};
