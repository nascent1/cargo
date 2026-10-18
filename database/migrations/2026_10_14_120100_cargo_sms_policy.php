<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cargo SMS policy: templates (en / sw) for booking details to sender and receiver, cancellation,
 * close of business, approval requests, account details and password resets; close-of-business
 * settings and the "Receive close-of-business SMS" permission.
 *
 * Removes settings nothing reads any more: sms_sender_id (the sender ID lives on each SMS gateway),
 * sms_cargo_enabled and cargo_payment_sms_enabled (replaced by sms_enabled + the two booking switches).
 */
return new class extends Migration
{
    private const TEMPLATES = [
        ['cargo_booking_sender', 'en', '{{company_name}}: Booking {{booking_reference}} confirmed. {{items}} on {{route}}, departing {{departure}} ({{vessel}}). Paid {{currency}} {{amount}}. Receiver: {{receiver_name}}. Track: {{tracking_url}}'],
        ['cargo_booking_sender', 'sw', '{{company_name}}: Booking {{booking_reference}} imethibitishwa. {{items}} kwa {{route}}, inaondoka {{departure}} ({{vessel}}). Umelipa {{currency}} {{amount}}. Mpokeaji: {{receiver_name}}. Fuatilia: {{tracking_url}}'],
        ['cargo_booking_receiver', 'en', '{{company_name}}: {{sender_name}} has sent you cargo, booking {{booking_reference}} ({{item_count}} item(s)) on {{route}}, departing {{departure}}. Bring your ID and this booking number to collect. Track: {{tracking_url}}'],
        ['cargo_booking_receiver', 'sw', '{{company_name}}: {{sender_name}} amekutumia mzigo, booking {{booking_reference}} (vipande {{item_count}}) kwa {{route}}, unaondoka {{departure}}. Leta kitambulisho na namba hii ya booking kuuchukua. Fuatilia: {{tracking_url}}'],
        ['cargo_cancelled', 'en', '{{company_name}}: Cargo booking {{booking_reference}} ({{item_count}} item(s)) has been cancelled. Contact us about any refund.'],
        ['cargo_cancelled', 'sw', '{{company_name}}: Booking ya mzigo {{booking_reference}} (vipande {{item_count}}) imefutwa. Wasiliana nasi kuhusu marejesho.'],
        ['close_of_business', 'en', '{{company_name}} close of business {{date}} ({{period}}): {{booking_count}} bookings, {{currency}} {{revenue}} collected ({{channel_breakdown}}). {{weight_kg}} kg. Unpaid {{unpaid_count}}, cancelled {{cancelled_count}}, refunds due {{refunds_due}}.{{shift_line}}'],
        ['close_of_business', 'sw', '{{company_name}} kufunga siku {{date}} ({{period}}): booking {{booking_count}}, {{currency}} {{revenue}} zimekusanywa ({{channel_breakdown}}). Kg {{weight_kg}}. Hazijalipwa {{unpaid_count}}, zimefutwa {{cancelled_count}}, marejesho {{refunds_due}}.{{shift_line}}'],
        ['dual_control_request', 'en', '{{company_name}}: {{count}} change(s) await your approval. Latest: {{label}} (#DC-{{request_id}}) by {{maker}}, takes effect {{takes_effect}}. {{url}}'],
        ['dual_control_request', 'sw', '{{company_name}}: mabadiliko {{count}} yanasubiri idhini yako. La mwisho: {{label}} (#DC-{{request_id}}) na {{maker}}, yanaanza {{takes_effect}}. {{url}}'],
        ['account_credentials', 'en', 'Welcome to {{company_name}}. Your {{account_type}} account is ready. Login: {{login_url}} Email: {{email}} Password: {{password}} Please change the password after you log in.'],
        ['account_credentials', 'sw', 'Karibu {{company_name}}. Akaunti yako ya {{account_type}} iko tayari. Ingia: {{login_url}} Barua pepe: {{email}} Nenosiri: {{password}} Tafadhali badilisha nenosiri ukishaingia.'],
        ['password_reset_link', 'en', '{{company_name}}: reset your password with this link (valid {{expires_minutes}} min): {{reset_url}} If you did not ask for this, ignore this message.'],
        ['password_reset_link', 'sw', '{{company_name}}: badilisha nenosiri lako kwa kiungo hiki (dakika {{expires_minutes}}): {{reset_url}} Kama hukuomba, puuza ujumbe huu.'],
        ['password_reset_by_admin', 'en', 'Hello {{first_name}}, an administrator reset your {{company_name}} password. Temporary password: {{temporary_password}} You must change it at your next login.'],
        ['password_reset_by_admin', 'sw', 'Habari {{first_name}}, msimamizi amebadilisha nenosiri lako la {{company_name}}. Nenosiri la muda: {{temporary_password}} Utatakiwa kulibadilisha ukiingia tena.'],
    ];

    private const SETTINGS = [
        'close_of_business_buffer_minutes' => ['30', 'Minutes after the last cargo departure of the day before the close-of-business SMS is sent'],
        'close_of_business_fallback_time' => ['20:00', 'Close-of-business time on days without departures (HH:MM)'],
    ];

    private const REMOVED = [
        'sms_sender_id' => ['FERRY', 'SMS sender ID', 'notification'],
        'sms_cargo_enabled' => ['1', 'Enable SMS notifications for cargo', 'sms'],
        'cargo_payment_sms_enabled' => ['1', 'Send an SMS to the sender when a cargo payment is received', 'cargo'],
    ];

    private const PERMISSION = 'close_of_business:receive';

    public function up(): void
    {
        $now = now();

        foreach (self::TEMPLATES as [$event, $locale, $body]) {
            DB::table('notification_templates')->insertOrIgnore([
                'event' => $event, 'channel' => 'sms', 'locale' => $locale, 'subject' => null,
                'body' => $body, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::SETTINGS as $key => [$value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => 'notification', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('system_configs')->where('key_name', 'sms_enabled')
            ->update(['description' => 'Master switch for customer, summary and approval SMS (account and password SMS always go out)', 'updated_at' => $now]);
        DB::table('system_configs')->where('key_name', 'sms_booking_confirmation_sender')
            ->update(['description' => 'SMS the sender the booking details when the booking is paid / confirmed', 'updated_at' => $now]);
        DB::table('system_configs')->where('key_name', 'sms_booking_confirmation_receiver')
            ->update(['description' => 'SMS the receiver the booking details when the booking is paid / confirmed', 'updated_at' => $now]);
        DB::table('system_configs')->whereIn('key_name', array_keys(self::REMOVED))->delete();

        DB::table('permissions')->updateOrInsert(['name' => self::PERMISSION], [
            'display_name' => 'Receive close-of-business SMS', 'module' => 'notification',
            'description' => 'Gets the daily close-of-business summary by SMS', 'is_system' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'admin', 'finance_officer'])->pluck('id') as $roleId) {
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $now = now();
        foreach (self::TEMPLATES as [$event, $locale]) {
            DB::table('notification_templates')->where(['event' => $event, 'channel' => 'sms', 'locale' => $locale])->delete();
        }
        DB::table('system_configs')->whereIn('key_name', array_keys(self::SETTINGS))->delete();
        foreach (self::REMOVED as $key => [$value, $description, $group]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => $group, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
