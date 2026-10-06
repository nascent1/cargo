<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo payment attempts — one consignment may have many attempts
     * (resend, switch channel), at most one of which ends up paid.
     */
    public function up(): void
    {
        Schema::create('cargo_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 40)->unique();            // our reference sent to the provider
            $table->foreignId('consignment_id')->constrained('consignments');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->string('flow', 20);                           // cash | ussd_push | lipa_namba
            $table->string('driver', 30)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('TZS');
            $table->string('payer_msisdn', 20)->nullable();
            $table->string('provider_request_id', 100)->nullable(); // provider conversation / order id
            $table->string('provider_reference', 100)->nullable();  // MNO receipt / transaction id
            $table->string('manual_reference', 100)->nullable()->unique(); // Lipa Namba code entered by officer
            // initiated → sending → awaiting_customer → paid | failed | expired | cancelled | refund_due
            $table->string('status', 20)->default('initiated');
            $table->text('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users');
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['driver', 'provider_reference']);
            $table->index('provider_request_id');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_payments');
    }
};
