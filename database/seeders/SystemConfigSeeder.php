<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $configs = [
            ['key_name' => 'platform_name',                     'value' => 'Victoria Ferries Booking Platform', 'description' => 'Platform display name',                               'group' => 'general',     'is_sensitive' => false],
            ['key_name' => 'default_locale',                    'value' => 'en',                                'description' => 'Default platform language',                           'group' => 'general',     'is_sensitive' => false],
            ['key_name' => 'platform_currency',                 'value' => 'TZS',                               'description' => 'Default currency code',                               'group' => 'general',     'is_sensitive' => false],
            ['key_name' => 'dc_expiry_hours',                   'value' => '48',                                'description' => 'Dual control request expiry in hours',                'group' => 'governance',  'is_sensitive' => false],
            ['key_name' => 'max_failed_logins',                 'value' => '5',                                 'description' => 'Max failed login attempts before lockout',            'group' => 'security',    'is_sensitive' => false],
            ['key_name' => 'lockout_duration_minutes',          'value' => '30',                                'description' => 'Account lockout duration in minutes',                 'group' => 'security',    'is_sensitive' => false],
            ['key_name' => 'session_timeout_minutes',           'value' => '120',                               'description' => 'Session timeout in minutes',                          'group' => 'security',    'is_sensitive' => false],
            ['key_name' => 'password_expiry_days',              'value' => '30',                                'description' => 'Days before requiring a mandatory password change (0 to disable)', 'group' => 'security',    'is_sensitive' => false],
            ['key_name' => 'ticket_prefix_default',             'value' => 'VFF',                               'description' => 'Default 3-character initial prefix for tickets',      'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'ticket_prefix_agent',               'value' => 'VAF',                               'description' => 'Default 3-character initial prefix for agent tickets','group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'booking_hold_minutes',              'value' => '15',                                'description' => 'Duration to hold seats before payment expiration',    'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'reservation_expiry_hours_before_departure', 'value' => '2',                         'description' => 'Hours before departure that unpaid reservations expire', 'group' => 'booking',  'is_sensitive' => false],
            ['key_name' => 'infant_free_mode',                  'value' => 'per_adult',                         'description' => 'Infant free mode (per_adult | per_booking)',          'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'infant_free_limit',                 'value' => '1',                                 'description' => 'Number of free infants allowed based on mode',        'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'cancellation_allowed_hours_before', 'value' => '2',                                 'description' => 'Cancellation allowed hours before departure (CAMH)',  'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'cancellation_penalty_percentage',   'value' => '10',                                'description' => 'Cancellation penalty percentage (CPP)',               'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'commission_default_method',         'value' => 'post_sale',                         'description' => 'Default agent commission calculation method',         'group' => 'agency',      'is_sensitive' => false],
            ['key_name' => 'commission_default_rate',           'value' => '5.00',                              'description' => 'Default percentage commission rate (%)',              'group' => 'agency',      'is_sensitive' => false],
            ['key_name' => 'security_deposit_default_rate',     'value' => '40.00',                             'description' => 'Default percentage held for security deposit (%)',    'group' => 'agency',      'is_sensitive' => false],
            ['key_name' => 'restrict_agent_routes',             'value' => '0',                                 'description' => 'Restrict agents to specific assigned routes',         'group' => 'agency',      'is_sensitive' => false],
            ['key_name' => 'restrict_agent_ferries',            'value' => '0',                                 'description' => 'Restrict agents to specific assigned ferries',        'group' => 'agency',      'is_sensitive' => false],
            ['key_name' => 'shift_management_enabled',          'value' => '1',                                 'description' => 'Enable strict shift hours enforcement for POS operators','group' => 'security',   'is_sensitive' => false],
            ['key_name' => 'default_shift_hours',               'value' => '8.00',                              'description' => 'Default hours allowed per operational shift',         'group' => 'security',    'is_sensitive' => false],

            // Cancellation Penalty Configuration
            ['key_name' => 'cancellation_penalty_enabled',      'value' => '1',                                 'description' => 'Master switch: enable cancellation penalties (0=off, 1=on)', 'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'cancellation_penalty_mode',         'value' => 'flat',                              'description' => 'Penalty calculation mode: flat (fixed %) or tiered (time-based %)', 'group' => 'booking',     'is_sensitive' => false],

            // CAMH Cancellation Thresholds (used when mode = tiered)
            ['key_name' => 'cancellation_full_penalty_hours',   'value' => '2',                                 'description' => 'Tiered mode: hours before departure for 100% penalty',  'group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'cancellation_partial_penalty_hours','value' => '24',                                'description' => 'Tiered mode: hours before departure for partial penalty','group' => 'booking',     'is_sensitive' => false],
            ['key_name' => 'cancellation_partial_penalty_rate', 'value' => '25',                                'description' => 'Tiered mode: partial cancellation penalty percentage',  'group' => 'booking',     'is_sensitive' => false],

            // Reschedule Configuration
            ['key_name' => 'reschedule_charge_fare_difference', 'value' => 'none',                             'description' => 'Fare difference handling on reschedule: none|charge_upgrade_only|charge_and_refund', 'group' => 'booking', 'is_sensitive' => false],
            ['key_name' => 'reschedule_penalty_percentage',     'value' => '10',                               'description' => 'Separate penalty percentage for reschedule (falls back to cancellation_penalty_percentage if empty)', 'group' => 'booking', 'is_sensitive' => false],

            // Payment
            ['key_name' => 'payment_gateway_default',           'value' => 'cash',                              'description' => 'Default payment gateway (cash|mpesa|stripe|selcom)',  'group' => 'payment',     'is_sensitive' => false],

            // Boarding
            ['key_name' => 'boarding_buffer_minutes',           'value' => '30',                                'description' => 'Minutes before departure to allow boarding',          'group' => 'operations',  'is_sensitive' => false],

            // SMS & Email
            ['key_name' => 'sms_enabled',                       'value' => '0',                                 'description' => 'Enable SMS notifications (requires Kilakona config)','group' => 'notification','is_sensitive' => false],
            ['key_name' => 'email_notifications_enabled',       'value' => '0',                                 'description' => 'Enable email notifications for booking events',      'group' => 'notification','is_sensitive' => false],
            ['key_name' => 'high_value_cancellation_threshold', 'value' => '500000',                            'description' => 'Threshold amount (TZS) for sending admin alerts on cancellation', 'group' => 'notification','is_sensitive' => false],
            
            // Branding & Security
            ['key_name' => 'company_name',                      'value' => 'Victoria Ferries Ltd',              'description' => 'Official company name for reports',                  'group' => 'general',     'is_sensitive' => false],
            ['key_name' => 'company_logo_url',                  'value' => '/images/logo.png',                  'description' => 'Path to company logo for reports',                   'group' => 'general',     'is_sensitive' => false],
            ['key_name' => 'enable_2fa',                        'value' => '0',                                 'description' => 'Enable 2FA globally for admin users (0=off, 1=on)',  'group' => 'security',    'is_sensitive' => false],
        ];

        foreach ($configs as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
            DB::table('system_configs')->updateOrInsert(['key_name' => $c['key_name']], $c);
        }
    }
}
