<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoSystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // Cargo booking settings
            ['group' => 'cargo', 'key_name' => 'cargo_hold_minutes_walkin',      'value' => '10',           'description' => 'Lock duration for walk-in customers (minutes)'],
            ['group' => 'cargo', 'key_name' => 'cargo_hold_minutes_agent',        'value' => '15',           'description' => 'Lock duration for agent bookings (minutes)'],
            ['group' => 'cargo', 'key_name' => 'cargo_hold_minutes_corporate',    'value' => '1440',         'description' => 'Lock duration for corporate accounts (24h)'],
            ['group' => 'cargo', 'key_name' => 'cargo_consignment_prefix',        'value' => 'CG',           'description' => 'Consignment number prefix'],
            ['group' => 'cargo', 'key_name' => 'cargo_receipt_prefix',            'value' => 'CR',           'description' => 'Receipt number prefix'],
            ['group' => 'cargo', 'key_name' => 'cargo_item_prefix',               'value' => 'CI',           'description' => 'Cargo item number prefix'],
            ['group' => 'cargo', 'key_name' => 'cargo_cancellation_penalty_pct',  'value' => '10',           'description' => 'Cancellation penalty percentage'],
            ['group' => 'cargo', 'key_name' => 'cargo_fragile_surcharge_pct',     'value' => '5',            'description' => 'Fragile item surcharge percentage'],
            ['group' => 'cargo', 'key_name' => 'cargo_hazmat_flat_fee_tzs',       'value' => '10000',        'description' => 'Hazmat declaration flat fee (TZS)'],
            ['group' => 'cargo', 'key_name' => 'cargo_hazmat_flat_fee_usd',       'value' => '5',            'description' => 'Hazmat declaration flat fee (USD)'],
            ['group' => 'cargo', 'key_name' => 'cargo_unclaimed_days',            'value' => '7',            'description' => 'Days before marking arrived cargo as unclaimed'],
            ['group' => 'cargo', 'key_name' => 'cargo_max_weight_per_item_kg',    'value' => '10000',        'description' => 'Maximum weight per cargo item (kg)'],
            ['group' => 'cargo', 'key_name' => 'cargo_default_currency',          'value' => 'TZS',          'description' => 'Default billing currency for cargo'],
            // Agent settings
            ['group' => 'agent', 'key_name' => 'agent_cargo_commission_rate',     'value' => '3.00',         'description' => 'Default agent commission % on cargo bookings'],
            ['group' => 'agent', 'key_name' => 'agent_cargo_commission_type',     'value' => 'percentage',   'description' => 'Commission type: percentage or fixed'],
            // Receipt / printing
            ['group' => 'receipt', 'key_name' => 'receipt_printer_type',          'value' => 'thermal_80mm', 'description' => 'Receipt printer type'],
            ['group' => 'receipt', 'key_name' => 'receipt_copies',                'value' => '2',            'description' => 'Default number of receipt copies to print'],
            ['group' => 'receipt', 'key_name' => 'receipt_show_qr',               'value' => '1',            'description' => 'Print QR code on receipt'],
            ['group' => 'receipt', 'key_name' => 'receipt_footer_text',           'value' => 'Thank you for using UCLP Cargo Services!', 'description' => 'Footer text on receipt'],
            // SMS
            ['group' => 'sms', 'key_name' => 'sms_cargo_enabled',                'value' => '1',            'description' => 'Enable SMS notifications for cargo'],
            ['group' => 'sms', 'key_name' => 'sms_booking_confirmation_sender',   'value' => '1',            'description' => 'Send SMS to sender on booking confirmation'],
            ['group' => 'sms', 'key_name' => 'sms_booking_confirmation_receiver', 'value' => '1',            'description' => 'Send SMS to receiver on booking confirmation'],
            // Tracking
            ['group' => 'tracking', 'key_name' => 'tracking_public_url',          'value' => '/track',       'description' => 'Public tracking URL path'],
            ['group' => 'tracking', 'key_name' => 'tracking_qr_base_url',         'value' => 'http://127.0.0.1:8001/track', 'description' => 'Base URL for QR code tracking links'],
        ];

        foreach ($configs as $config) {
            DB::table('system_configs')->updateOrInsert(
                ['key_name' => $config['key_name']],
                array_merge($config, [
                    'is_sensitive' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
