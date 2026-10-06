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
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('discount_type', 30)->nullable()->change();
            $table->decimal('discount_value', 10, 2)->nullable()->change();
            $table->decimal('max_discount', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('code', 50)->unique()->nullable();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('discount_type', 30)->nullable(false)->change();
            $table->decimal('discount_value', 10, 2)->nullable(false)->change();
            $table->decimal('max_discount', 10, 2)->nullable(false)->change();
        });
    }
};
