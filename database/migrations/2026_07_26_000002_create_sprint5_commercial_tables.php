<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Price Discounts — automatic discounts based on route, class, passenger type
        if (!Schema::hasTable('price_discounts')) {
            Schema::create('price_discounts', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('discount_type', 30); // percentage, fixed_amount
                $table->decimal('discount_value', 10, 2);
                $table->string('applies_to', 30)->default('all'); // all, passenger_type, travel_class, route
                $table->unsignedBigInteger('passenger_type_id')->nullable();
                $table->unsignedBigInteger('travel_class_id')->nullable();
                $table->unsignedBigInteger('route_id')->nullable();
                $table->decimal('min_passengers', 5, 0)->nullable(); // group discount threshold
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('priority')->default(0); // higher priority wins
                $table->timestamps();

                $table->index(['is_active', 'valid_from', 'valid_to']);
            });
        }

        // 2. Promotions — time-limited campaigns with optional budgets
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->unique()->nullable(); // promotional code
                $table->text('description')->nullable();
                $table->string('discount_type', 30); // percentage, fixed_amount
                $table->decimal('discount_value', 10, 2);
                $table->decimal('max_discount', 10, 2)->nullable(); // cap for percentage discounts
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('budget', 12, 2)->nullable(); // max total discount budget
                $table->decimal('budget_used', 12, 2)->default(0);
                $table->unsignedInteger('max_uses')->nullable(); // total redemption limit
                $table->unsignedInteger('current_uses')->default(0);
                $table->unsignedBigInteger('route_id')->nullable(); // restrict to specific route
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'start_date', 'end_date']);
            });
        }

        // 3. Coupons — individual-use discount codes
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->unsignedBigInteger('promotion_id')->nullable();
                $table->string('discount_type', 30); // percentage, fixed_amount
                $table->decimal('discount_value', 10, 2);
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->unsignedInteger('max_uses')->default(1);
                $table->unsignedInteger('current_uses')->default(0);
                $table->date('valid_from');
                $table->date('valid_to');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('code');
                $table->foreign('promotion_id')->references('id')->on('promotions')->nullOnDelete();
            });
        }

        // 4. Coupon Usages — audit trail
        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('discount_applied', 10, 2);
                $table->timestamps();

                $table->unique(['coupon_id', 'booking_id']);
            });
        }

        // 5. Complimentary Tickets — free tickets issued by authorized personnel
        if (!Schema::hasTable('complimentary_tickets')) {
            Schema::create('complimentary_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
                $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 255);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complimentary_tickets');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('price_discounts');
    }
};
