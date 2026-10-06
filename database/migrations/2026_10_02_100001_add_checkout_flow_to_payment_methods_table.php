<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Channels become configuration: each payment method declares how checkout
     * collects money (flow) and which integration performs it (driver).
     */
    private const USSD_DRIVERS = [
        'mpesa'        => 'vodacom_mpesa',
        'airtel_money' => 'airtel_money',
        'tigopesa'     => 'tigo_pesa',
        'halopesa'     => 'halopesa',
    ];

    // Suspended until their integrations are built
    private const SUSPENDED = [
        'crdb_bank', 'nmb_bank', 'visa_mastercard', 'mobile-money', 'card',
        'agent-wallet-deduction', 'agent-float',
    ];

    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('flow', 20)->nullable()->after('name');       // cash | ussd_push | lipa_namba
            $table->string('driver', 30)->nullable()->after('flow');     // push integration for ussd_push
            $table->unsignedSmallInteger('sort_order')->default(100)->after('driver');
            $table->string('account_number', 50)->nullable()->after('sort_order'); // Lipa Namba till
            $table->string('account_name', 100)->nullable()->after('account_number');
            $table->text('instructions')->nullable()->after('account_name');
        });

        DB::table('payment_methods')->where('slug', 'cash')->update(['flow' => 'cash', 'sort_order' => 10]);

        $order = 20;
        foreach (self::USSD_DRIVERS as $slug => $driver) {
            DB::table('payment_methods')->where('slug', $slug)
                ->update(['flow' => 'ussd_push', 'driver' => $driver, 'sort_order' => $order]);
            $order += 10;
        }

        DB::table('payment_methods')->whereIn('slug', self::SUSPENDED)->update(['is_active' => false]);

        $now = now();
        DB::table('payment_methods')->insertOrIgnore([
            [
                'slug' => 'selcom_pesa', 'name' => 'Selcom Pesa', 'flow' => 'ussd_push', 'driver' => 'selcom_pesa',
                'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                'instructions' => null,
            ],
            [
                'slug' => 'lipa_namba', 'name' => 'Lipa Namba', 'flow' => 'lipa_namba', 'driver' => null,
                'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                'instructions' => "Dial your mobile money menu, choose Lipa kwa Simu / Lipa Namba, enter the Lipa Namba and the exact amount. Quote the booking reference.",
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')->whereIn('slug', ['selcom_pesa', 'lipa_namba'])->delete();
        DB::table('payment_methods')->whereIn('slug', self::SUSPENDED)->update(['is_active' => true]);

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['flow', 'driver', 'sort_order', 'account_number', 'account_name', 'instructions']);
        });
    }
};
