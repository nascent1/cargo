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
            $table->foreignId('agent_account_id')->nullable()->after('user_id')->constrained('agent_accounts')->nullOnDelete();
            $table->decimal('amount_held', 15, 4)->default(0)->after('agent_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_locks', function (Blueprint $table) {
            $table->dropForeign(['agent_account_id']);
            $table->dropColumn(['agent_account_id', 'amount_held']);
        });
    }
};
