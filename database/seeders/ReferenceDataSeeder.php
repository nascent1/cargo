<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use App\Models\Nationality;
use App\Models\Currency;
use App\Models\ExchangeRate;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Countries
        $countries = [
            ['name' => 'Tanzania', 'code' => 'TZ', 'phone_code' => '+255', 'is_active' => true],
            ['name' => 'Kenya', 'code' => 'KE', 'phone_code' => '+254', 'is_active' => true],
            ['name' => 'Uganda', 'code' => 'UG', 'phone_code' => '+256', 'is_active' => true],
            ['name' => 'United States', 'code' => 'US', 'phone_code' => '+1', 'is_active' => true],
            ['name' => 'United Kingdom', 'code' => 'GB', 'phone_code' => '+44', 'is_active' => true],
            ['name' => 'Germany', 'code' => 'DE', 'phone_code' => '+49', 'is_active' => true],
            ['name' => 'France', 'code' => 'FR', 'phone_code' => '+33', 'is_active' => true],
            ['name' => 'South Africa', 'code' => 'ZA', 'phone_code' => '+27', 'is_active' => true],
        ];

        foreach ($countries as $c) {
            Country::updateOrCreate(['code' => $c['code']], $c);
        }

        // 2. Nationalities
        $nationalities = [
            ['name' => 'Tanzanian', 'code' => 'TZA', 'is_active' => true],
            ['name' => 'Kenyan', 'code' => 'KEN', 'is_active' => true],
            ['name' => 'Ugandan', 'code' => 'UGA', 'is_active' => true],
            ['name' => 'American', 'code' => 'USA', 'is_active' => true],
            ['name' => 'British', 'code' => 'GBR', 'is_active' => true],
            ['name' => 'German', 'code' => 'DEU', 'is_active' => true],
            ['name' => 'French', 'code' => 'FRA', 'is_active' => true],
            ['name' => 'South African', 'code' => 'ZAF', 'is_active' => true],
        ];

        foreach ($nationalities as $n) {
            Nationality::updateOrCreate(['code' => $n['code']], $n);
        }

        // 3. Currencies
        $currencies = [
            ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'is_active' => true, 'is_default' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true, 'is_default' => false],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'is_active' => true, 'is_default' => false],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true, 'is_default' => false],
        ];

        foreach ($currencies as $curr) {
            Currency::updateOrCreate(['code' => $curr['code']], $curr);
        }

        // 4. Exchange Rates
        $rates = [
            ['from_currency' => 'USD', 'to_currency' => 'TZS', 'rate' => 2600.00, 'effective_from' => '2026-01-01'],
            ['from_currency' => 'EUR', 'to_currency' => 'TZS', 'rate' => 2850.00, 'effective_from' => '2026-01-01'],
            ['from_currency' => 'KES', 'to_currency' => 'TZS', 'rate' => 20.50, 'effective_from' => '2026-01-01'],
            ['from_currency' => 'TZS', 'to_currency' => 'TZS', 'rate' => 1.00, 'effective_from' => '2026-01-01'],
        ];

        foreach ($rates as $r) {
            ExchangeRate::updateOrCreate([
                'from_currency' => $r['from_currency'],
                'to_currency' => $r['to_currency'],
                'effective_from' => $r['effective_from'],
            ], $r);
        }

        // 5. Identity Types (for identity_types table)
        $idTypes = [
            ['slug' => 'passport', 'name' => 'Passport', 'is_active' => true],
            ['slug' => 'national_id', 'name' => 'National ID / NIDA', 'is_active' => true],
            ['slug' => 'voters_id', 'name' => 'Voter Card', 'is_active' => true],
            ['slug' => 'driving_license', 'name' => 'Driving License', 'is_active' => true],
            ['slug' => 'child_birth_cert', 'name' => 'Birth Certificate / Child', 'is_active' => true],
        ];

        foreach ($idTypes as $idt) {
            DB::table('identity_types')->updateOrInsert(['slug' => $idt['slug']], [
                'name' => $idt['name'],
                'is_active' => $idt['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
