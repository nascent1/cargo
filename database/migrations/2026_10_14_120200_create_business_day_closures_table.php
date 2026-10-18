<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per business day closed (after the day's last cargo departure): the period it covers,
 * the figures sent in the close-of-business SMS and who received them. Reporting only — it
 * doesn't lock anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_day_closures', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('trigger', 20); // last_departure | fallback | manual
            $table->unsignedBigInteger('last_service_id')->nullable(); // transport_services.id that set the cut-off
            $table->json('summary');
            $table->json('deliveries')->nullable(); // user_id => sent|failed|skipped
            $table->unsignedSmallInteger('recipients_count')->default(0);
            $table->unsignedSmallInteger('sent_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_day_closures');
    }
};
