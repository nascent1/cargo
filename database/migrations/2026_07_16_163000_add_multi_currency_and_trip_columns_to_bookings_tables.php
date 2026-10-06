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
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'trip_type')) {
                $table->string('trip_type', 20)->default('one_way')->after('booking_reference')->comment('one_way or round_trip');
            }
            if (!Schema::hasColumn('bookings', 'total_amount_tzs')) {
                $table->decimal('total_amount_tzs', 12, 2)->default(0.00)->after('total_amount')->comment('Exact TZS collected/subtotal');
            }
            if (!Schema::hasColumn('bookings', 'total_amount_usd')) {
                $table->decimal('total_amount_usd', 12, 2)->default(0.00)->after('total_amount_tzs')->comment('Exact USD collected/subtotal');
            }
            if (!Schema::hasColumn('bookings', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 2)->default(2600.00)->after('total_amount_usd')->comment('Reference TZS/USD exchange rate');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'leg_type')) {
                $table->string('leg_type', 20)->default('outbound')->after('booking_id')->comment('outbound or inbound');
            }
            if (!Schema::hasColumn('tickets', 'currency')) {
                $table->string('currency', 10)->default('TZS')->after('final_fare')->comment('TZS or USD');
            }
            if (!Schema::hasColumn('tickets', 'nationality_type')) {
                $table->tinyInteger('nationality_type')->default(1)->after('passenger_id')->comment('1 = Resident, 2 = Foreigner');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['trip_type', 'total_amount_tzs', 'total_amount_usd', 'exchange_rate']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['leg_type', 'currency', 'nationality_type']);
        });
    }
};
