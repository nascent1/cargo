<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CONFIGS = [
        ['cargo_ussd_timeout_minutes', '15', 'Minutes to wait for a USSD push payment before the booking is auto-cancelled'],
        ['cargo_lipa_timeout_minutes', '30', 'Minutes to wait for Lipa Namba confirmation before the booking is auto-cancelled'],
        ['cargo_ussd_max_pushes', '3', 'Maximum USSD push attempts per booking (resends and network switches)'],
        ['cargo_payment_sms_enabled', '1', 'Send an SMS to the sender when a cargo payment is received'],
        ['cargo_payment_simulator_enabled', '1', 'Local/dev only: simulate unconfigured USSD push channels (never active in production)'],
    ];

    // One credentials row per push driver; inactive until filled in from Settings › Payment Channels
    private const PROVIDERS = [
        ['VODACOM_MPESA_TZ', 'Vodacom M-Pesa (OpenAPI)', 'vodacom_mpesa', 'https://openapi.m-pesa.com/sandbox', 'https://openapi.m-pesa.com/openapi', 10],
        ['AIRTEL_MONEY_TZ', 'Airtel Money (Open API)', 'airtel_money', 'https://openapiuat.airtel.africa', 'https://openapi.airtel.africa', 20],
        ['TIGO_PESA_TZ', 'Tigo Pesa (Push Bill Pay)', 'tigo_pesa', null, null, 30],
        ['HALOPESA_TZ', 'Halopesa (USSD Push)', 'halopesa', null, null, 40],
        ['SELCOM_PESA', 'Selcom Pesa (Checkout API)', 'selcom_pesa', 'https://apigw.selcommobile.com', 'https://apigw.selcommobile.com', 50],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::CONFIGS as [$key, $value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => 'cargo', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::PROVIDERS as [$code, $name, $provider, $sandbox, $production, $sort]) {
            if (DB::table('integration_configs')->where('code', $code)->exists()) {
                continue;
            }
            DB::table('integration_configs')->insert([
                'id' => (string) Str::uuid(), 'code' => $code, 'name' => $name, 'provider' => $provider,
                'channel' => 'payment', 'environment' => 'sandbox',
                'base_url' => $sandbox, 'sandbox_url' => $sandbox, 'production_url' => $production,
                'is_active' => false, 'is_default' => false, 'currency' => 'TZS', 'country_code' => 'TZ',
                'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_configs')->whereIn('key_name', array_column(self::CONFIGS, 0))->delete();
        DB::table('integration_configs')->whereIn('code', array_column(self::PROVIDERS, 0))->delete();
    }
};
