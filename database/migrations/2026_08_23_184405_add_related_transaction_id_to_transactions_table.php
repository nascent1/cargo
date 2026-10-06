<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('related_transaction_id')
                ->nullable()
                ->after('transaction_status_id')
                ->constrained('transactions')
                ->nullOnDelete();

            $table->index('related_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['related_transaction_id']);
            $table->dropColumn('related_transaction_id');
        });
    }
};
