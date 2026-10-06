<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo receipts — consignment note metadata for 80mm thermal POS printing.
     */
    public function up(): void
    {
        Schema::create('cargo_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained('consignments');
            $table->string('receipt_number', 60)->unique();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users');
            $table->timestamp('issued_at')->useCurrent();
            $table->integer('print_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_receipts');
    }
};
