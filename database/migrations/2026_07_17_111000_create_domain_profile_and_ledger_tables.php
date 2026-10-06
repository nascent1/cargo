<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. TRANSACTION CATEGORIES (Lookup table for normalized financial categories)
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('type', 10); // 'credit' or 'debit'
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('transaction_categories')->insert([
            ['id' => 1, 'name' => 'Float Top-up', 'slug' => 'float-top-up', 'type' => 'credit', 'color' => 'green', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Ticket Sale', 'slug' => 'ticket-sale', 'type' => 'debit', 'color' => 'blue', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Commission', 'slug' => 'commission', 'type' => 'credit', 'color' => 'purple', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Reversal', 'slug' => 'reversal', 'type' => 'credit', 'color' => 'yellow', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Refund', 'slug' => 'refund', 'type' => 'debit', 'color' => 'orange', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Adjustment (Credit)', 'slug' => 'adjustment-credit', 'type' => 'credit', 'color' => 'gray', 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Adjustment (Debit)', 'slug' => 'adjustment-debit', 'type' => 'debit', 'color' => 'gray', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Cancellation Penalty', 'slug' => 'cancellation-penalty', 'type' => 'credit', 'color' => 'red', 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. TRANSACTION STATUSES (Lookup table for normalized statuses)
        Schema::create('transaction_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('transaction_statuses')->insert([
            ['id' => 1, 'name' => 'Pending', 'slug' => 'pending', 'color' => 'yellow', 'icon' => 'clock', 'sort_order' => 1, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Approved', 'slug' => 'approved', 'color' => 'green', 'icon' => 'check-circle', 'sort_order' => 2, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Rejected', 'slug' => 'rejected', 'color' => 'red', 'icon' => 'x-circle', 'sort_order' => 3, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Completed', 'slug' => 'completed', 'color' => 'blue', 'icon' => 'check', 'sort_order' => 4, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. AGENTS (Ferry Sales Partners & Agencies)
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('agency_name', 150);
            $table->string('agent_code', 50)->unique(); // Internal agency/ticket code
            $table->string('agent_type', 30)->default('direct'); // 'direct', 'reseller', 'corporate'
            $table->decimal('commission_rate', 5, 2)->nullable()->default(null); // e.g., 5.00% (null = system default)
            $table->string('commission_method', 20)->nullable()->default(null); // 'post_sale', 'pre_deposit' (null = system default)
            $table->string('contact_mobile', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. ASSOCIATE AGENTS (Sub-agents / Staff members belonging to an Agency)
        Schema::create('associate_agents', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('associate_name', 150);
            $table->string('contact_mobile', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->decimal('daily_credit_limit', 18, 4)->nullable()->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. AGENT ACCOUNTS (Virtual float / credit balances for Agencies)
        Schema::create('agent_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->string('account_number', 50)->unique();
            $table->string('currency', 3)->default('TZS');
            $table->decimal('balance', 18, 4)->default(0.0000); // Current float balance
            $table->decimal('security_deposit_balance', 18, 4)->default(0.0000); // Held security deposit (40% hold)
            $table->decimal('minimum_balance', 18, 4)->default(0.0000);
            $table->decimal('maximum_balance', 18, 4)->nullable();
            $table->decimal('credit_limit', 18, 4)->nullable()->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_accounts');
        Schema::dropIfExists('associate_agents');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('transaction_statuses');
        Schema::dropIfExists('transaction_categories');
    }
};
