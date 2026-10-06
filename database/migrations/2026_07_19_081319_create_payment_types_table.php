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
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g. 'debit', 'credit'
            $table->string('name'); // e.g. 'Debit', 'Credit'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default payment types
        DB::table('payment_types')->insert([
            ['slug' => 'debit', 'name' => 'Debit', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'credit', 'name' => 'Credit', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'reverse', 'name' => 'Reverse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'refund', 'name' => 'Refund', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'payment', 'name' => 'Payment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_types');
    }
};
