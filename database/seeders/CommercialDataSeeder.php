<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceDiscount;
use App\Models\Promotion;
use App\Models\Coupon;
use App\Models\Announcement;
use App\Models\IntegrationConfig;
use App\Models\Corporate;

class CommercialDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Corporate Partner Accounts ──────────────────────
        $corporates = [
            [
                'name' => 'Zanzibar Tourism Board',
                'registration_number' => 'ZTB-2026-001',
                'contact_person' => 'Amina Hassan',
                'email' => 'corporate@ztb.go.tz',
                'phone' => '+255 777 111 222',
                'address' => 'Stone Town, Zanzibar',
                'payment_terms_days' => 30,
                'credit_limit' => 15000000.00,
                'current_balance' => 0.00,
                'status' => 'active',
            ],
            [
                'name' => 'Azam Marine Partners Ltd',
                'registration_number' => 'AMP-8829-TZ',
                'contact_person' => 'Salum Said',
                'email' => 'bookings@azammarine.co.tz',
                'phone' => '+255 777 333 444',
                'address' => 'Malindi Port, Zanzibar',
                'payment_terms_days' => 30,
                'credit_limit' => 25000000.00,
                'current_balance' => 1500000.00,
                'status' => 'active',
            ],
            [
                'name' => 'Vodacom Tanzania Corporate',
                'registration_number' => 'VDC-9921-TZ',
                'contact_person' => 'John Mushi',
                'email' => 'travel@vodacom.co.tz',
                'phone' => '+255 754 100 100',
                'address' => 'Dar es Salaam, Tanzania',
                'payment_terms_days' => 45,
                'credit_limit' => 50000000.00,
                'current_balance' => 0.00,
                'status' => 'active',
            ],
        ];

        foreach ($corporates as $corp) {
            Corporate::updateOrCreate(['registration_number' => $corp['registration_number']], $corp);
        }

        // ── Price Discounts ─────────────────────────────────
        $discounts = [
            [
                'name'           => 'Child Discount (50%)',
                'discount_type'  => 'percentage',
                'discount_value' => 50,
                'applies_to'     => 'passenger_type',
                'passenger_type_id' => 2, // Child
                'priority'       => 10,
            ],
            [
                'name'           => 'Infant Free',
                'discount_type'  => 'percentage',
                'discount_value' => 100,
                'applies_to'     => 'passenger_type',
                'passenger_type_id' => 3, // Infant
                'priority'       => 20,
            ],
            [
                'name'           => 'Group Discount (10+ pax)',
                'discount_type'  => 'percentage',
                'discount_value' => 10,
                'applies_to'     => 'all',
                'min_passengers' => 10,
                'priority'       => 5,
            ],
        ];

        foreach ($discounts as $d) {
            PriceDiscount::updateOrCreate(
                ['name' => $d['name']],
                array_merge($d, ['is_active' => true])
            );
        }

        // ── Promotions ──────────────────────────────────────
        Promotion::updateOrCreate(
            ['name' => 'Welcome to Ferry 2026'],
            [
                'description'    => 'First-time booking promotion — 15% off any route.',
                'discount_type'  => 'percentage',
                'discount_value' => 15,
                'max_discount'   => 10000,
                'start_date'     => now()->startOfMonth(),
                'end_date'       => now()->addMonths(3)->endOfMonth(),
                'budget'         => 500000,
                'max_uses'       => 1000,
                'is_active'      => true,
            ]
        );

        Promotion::updateOrCreate(
            ['name' => 'Weekend Ferry Special'],
            [
                'description'    => 'TZS 2000 off weekend sailings.',
                'discount_type'  => 'fixed_amount',
                'discount_value' => 2000,
                'start_date'     => now(),
                'end_date'       => now()->addMonths(6),
                'max_uses'       => 500,
                'is_active'      => true,
            ]
        );

        // ── Coupons ─────────────────────────────────────────
        $karibuPromo = Promotion::where('name', 'Welcome to Ferry 2026')->first();

        if ($karibuPromo) {
            Coupon::updateOrCreate(
                ['code' => 'KARIBU2026'],
                [
                    'promotion_id'   => $karibuPromo->id,
                    'discount_type'  => 'percentage',
                    'discount_value' => 15,
                    'max_discount'   => 10000,
                    'max_uses'       => 1000,
                    'valid_from'     => $karibuPromo->start_date,
                    'valid_to'       => $karibuPromo->end_date,
                    'is_active'      => true,
                ]
            );
        }

        Coupon::updateOrCreate(
            ['code' => 'VIP50'],
            [
                'discount_type'  => 'fixed_amount',
                'discount_value' => 5000,
                'max_uses'       => 50,
                'valid_from'     => now(),
                'valid_to'       => now()->addMonths(12),
                'is_active'      => true,
            ]
        );

        // ── Announcements ───────────────────────────────────
        Announcement::updateOrCreate(
            ['title' => 'Welcome to Ferry Booking Platform'],
            [
                'message'         => 'Our new digital ticketing system is now live. Book your ferry tickets online or through our agents.',
                'type'            => 'info',
                'target_audience' => 'all',
                'start_date'      => now(),
                'end_date'        => now()->addMonths(1),
                'is_active'       => true,
            ]
        );

        // ── Integration Configs ─────────────────────────────
        IntegrationConfig::updateOrCreate(
            ['provider' => 'kilakona_sms'],
            [
                'type'       => 'sms',
                'config'     => [
                    'api_key'    => env('KILAKONA_API_KEY', ''),
                    'sender_id'  => env('KILAKONA_SENDER_ID', 'FERRY'),
                    'base_url'   => 'https://sms.kilakona.co.tz/api/v1',
                ],
                'is_active'  => false,
                'is_sandbox' => true,
            ]
        );
    }
}
