<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // owner/passenger account
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->foreignId('id_type_id')->nullable()->constrained('id_types')->nullOnDelete();
            $table->string('id_number', 100)->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality', 100)->default('Tanzanian');
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('is_saved_profile')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_saved_profile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
