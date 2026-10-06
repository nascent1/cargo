<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── Booking Lifecycle ───────────────────────────
            [
                'event'   => 'booking_confirmed',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Your booking {{booking_reference}} is confirmed! {{passenger_count}} passenger(s) on {{route_name}} departing {{departure_date}} at {{departure_time}}. Fare: {{currency}} {{total_amount}}.',
            ],
            [
                'event'   => 'booking_confirmed',
                'channel' => 'sms',
                'locale'  => 'sw',
                'body'    => 'Tikiti yako {{booking_reference}} imethibitishwa! Abiria {{passenger_count}} kwenye {{route_name}} kuondoka {{departure_date}} saa {{departure_time}}. Nauli: {{currency}} {{total_amount}}.',
            ],
            [
                'event'   => 'booking_confirmed',
                'channel' => 'email',
                'locale'  => 'en',
                'subject' => 'Booking Confirmed — {{booking_reference}}',
                'body'    => "Dear {{passenger_name}},\n\nYour ferry booking has been confirmed.\n\nBooking Reference: {{booking_reference}}\nRoute: {{route_name}}\nDeparture: {{departure_date}} at {{departure_time}}\nClass: {{travel_class}}\nTotal: {{currency}} {{total_amount}}\n\nPlease arrive at the port at least 30 minutes before departure.\n\nSafe travels!",
            ],
            [
                'event'   => 'booking_confirmed',
                'channel' => 'in_app',
                'locale'  => 'en',
                'body'    => 'Booking {{booking_reference}} confirmed for {{route_name}} on {{departure_date}} at {{departure_time}}.',
            ],

            // ── Payment ─────────────────────────────────────
            [
                'event'   => 'payment_received',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Payment of {{currency}} {{amount}} received for booking {{booking_reference}} via {{payment_method}}. Ref: {{transaction_reference}}.',
            ],
            [
                'event'   => 'payment_received',
                'channel' => 'sms',
                'locale'  => 'sw',
                'body'    => 'Malipo ya {{currency}} {{amount}} yamepokelewa kwa tikiti {{booking_reference}} kupitia {{payment_method}}. Nambari: {{transaction_reference}}.',
            ],

            // ── Cancellation ────────────────────────────────
            [
                'event'   => 'booking_cancelled',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Booking {{booking_reference}} has been cancelled. Refund of {{currency}} {{refund_amount}} will be processed within 3 business days.',
            ],
            [
                'event'   => 'booking_cancelled',
                'channel' => 'sms',
                'locale'  => 'sw',
                'body'    => 'Tikiti {{booking_reference}} imefutwa. Fedha za {{currency}} {{refund_amount}} zitarudishwa ndani ya siku 3 za kazi.',
            ],

            // ── Departure Reminder ──────────────────────────
            [
                'event'   => 'departure_reminder',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Reminder: Your ferry departs tomorrow at {{departure_time}} from {{origin_port}}. Booking: {{booking_reference}}. Please arrive 30 min early.',
            ],
            [
                'event'   => 'departure_reminder',
                'channel' => 'sms',
                'locale'  => 'sw',
                'body'    => 'Kumbuka: Feri yako inaondoka kesho saa {{departure_time}} kutoka {{origin_port}}. Tikiti: {{booking_reference}}. Tafadhali fika mapema dakika 30.',
            ],

            // ── Agent Float ─────────────────────────────────
            [
                'event'   => 'float_topup_approved',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Float top-up of {{currency}} {{amount}} has been approved for your agency {{agency_name}}. New balance: {{currency}} {{new_balance}}.',
            ],
            [
                'event'   => 'float_low_balance',
                'channel' => 'sms',
                'locale'  => 'en',
                'body'    => 'Warning: Your agency {{agency_name}} float balance is low ({{currency}} {{balance}}). Please request a top-up to avoid booking disruptions.',
            ],

            // ── Vessel Certificate ──────────────────────────
            [
                'event'   => 'certificate_expiring',
                'channel' => 'email',
                'locale'  => 'en',
                'subject' => 'Vessel Certificate Expiring — {{vessel_name}}',
                'body'    => "Certificate #{{certificate_number}} for vessel {{vessel_name}} will expire on {{expiry_date}}.\n\nPlease arrange renewal with the issuing authority ({{issued_by}}).",
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                [
                    'event'   => $template['event'],
                    'channel' => $template['channel'],
                    'locale'  => $template['locale'],
                ],
                [
                    'subject'   => $template['subject'] ?? null,
                    'body'      => $template['body'],
                    'is_active' => true,
                ]
            );
        }
    }
}
