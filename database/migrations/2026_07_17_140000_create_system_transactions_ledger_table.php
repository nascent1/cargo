<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_number', 64)->unique();
                
                // Transaction Classification via Lookup Table (Normalized)
                // Foreign key to transaction_categories (1: Float Top-up, 2: Ticket Sale, 3: Commission, 4: Reversal, 5: Refund, 6: Adjustment Credit, 7: Adjustment Debit, 8: Cancellation Penalty)
                $table->foreignId('transaction_category_id')->constrained('transaction_categories');
                
                // Type: 'credit' (inflow/addition), 'debit' (outflow/deduction), 'reverse'
                $table->string('type', 20)->index();
                
                // Monetary Amounts
                $table->decimal('amount', 12, 2);
                $table->string('currency_code', 10)->default('TZS');
                $table->decimal('exchange_rate', 12, 6)->default(1.000000);
                $table->decimal('amount_tzs', 12, 2); // Base currency equivalent for uniform platform accounting & reporting
                
                // Wallet / Float Snapshot Tracking (when applicable)
                $table->decimal('balance_before', 12, 2)->nullable();
                $table->decimal('balance_after', 12, 2)->nullable();
                
                // Payment Channel details
                $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
                $table->string('payment_reference', 100)->nullable()->index(); // e.g. M-Pesa receipt, bank ref, cheque no
                
                // Polymorphic / Contextual Links
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Originating User (Agent, Staff, or System Channel)
                
                // Descriptive & Audit Information via Lookup Table (Normalized)
                // Foreign key to transaction_statuses (1: pending, 2: approved, 3: rejected, 4: completed)
                $table->foreignId('transaction_status_id')->default(4)->constrained('transaction_statuses');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable(); // Extra details (e.g. gateway JSON response, dual-control approval ID)
                
                $table->timestamps();
                
                // Compound indexes for lightning-fast reports and ledgers
                $table->index(['transaction_category_id', 'created_at'], 'idx_sys_tx_cat_created');
                $table->index(['user_id', 'transaction_category_id', 'created_at'], 'idx_sys_tx_usr_cat_cr');
                $table->index(['booking_id', 'transaction_category_id'], 'idx_sys_tx_bk_cat');
                $table->index(['transaction_status_id', 'created_at'], 'idx_sys_tx_status_cr');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
