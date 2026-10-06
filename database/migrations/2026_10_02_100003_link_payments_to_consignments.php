<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });

        Schema::table('payment_gateway_logs', function (Blueprint $table) {
            $table->foreignId('consignment_id')->nullable()->after('booking_id')->constrained('consignments')->nullOnDelete();
            $table->foreignId('cargo_payment_id')->nullable()->after('consignment_id')->constrained('cargo_payments')->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('consignment_id')->nullable()->after('ticket_id')->constrained('consignments')->nullOnDelete();
        });

        // Align with the payment_statuses lookup (unpaid / paid / failed …); 'pending' is not in it
        DB::table('consignments')->where('payment_status', 'pending')->update(['payment_status' => 'unpaid']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consignment_id');
        });

        Schema::table('payment_gateway_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_payment_id');
            $table->dropConstrainedForeignId('consignment_id');
        });

        Schema::table('consignments', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
