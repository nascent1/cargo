<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voyage_id')->index();
            $table->unsignedBigInteger('ferry_class_id')->index();
            $table->string('seat_number', 50)->nullable()->index()->comment('Specific seat like 14A if has_layout=1, or null if open pool');
            $table->integer('quantity')->default(1)->comment('Number of seats locked by this hold');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('Maker user who created hold');
            $table->string('session_id', 100)->nullable()->index()->comment('Browser session ID or counter terminal ID');
            $table->timestamp('expires_at')->index()->comment('When this lock expires (e.g., now + 5 mins)');
            $table->timestamps();

            $table->foreign('voyage_id')->references('id')->on('voyages')->onDelete('cascade');
            $table->foreign('ferry_class_id')->references('id')->on('ferry_classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_locks');
    }
};
