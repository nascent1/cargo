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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payment_type_id')->nullable()->after('transaction_category_id')->constrained('payment_types')->nullOnDelete();
        });

        // Migrate data
        DB::statement("UPDATE transactions SET payment_type_id = (SELECT id FROM payment_types WHERE slug = transactions.type) WHERE type IS NOT NULL");

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['debit', 'credit'])->after('transaction_category_id')->nullable();
        });

        DB::statement("UPDATE transactions SET type = (SELECT slug FROM payment_types WHERE id = transactions.payment_type_id) WHERE payment_type_id IS NOT NULL");

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_type_id']);
            $table->dropColumn('payment_type_id');
        });
    }
};
