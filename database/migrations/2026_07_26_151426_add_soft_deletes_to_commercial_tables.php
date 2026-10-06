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
        Schema::table('price_discounts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->softDeletes();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->change();
            $table->date('valid_to')->nullable()->change();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_discounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->dropSoftDeletes();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->date('valid_from')->nullable(false)->change();
            $table->date('valid_to')->nullable(false)->change();
            $table->dropSoftDeletes();
        });
    }
};
