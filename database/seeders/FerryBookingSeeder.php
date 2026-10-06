<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Passenger;
use App\Models\Voyage;
use App\Models\FerryClass;
use App\Models\User;
use App\Models\BookingLog;
use Carbon\Carbon;

class FerryBookingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@ferrybooking.co.tz')->first();
        if (!$admin) return;

        $voyages = Voyage::with(['route', 'ferry', 'schedule'])->where('departure_date', '>=', now()->format('Y-m-d'))->get();
        if ($voyages->isEmpty()) return;

        $names = [
            ['first' => 'Juma', 'last' => 'Mussa', 'phone' => '+255712345678', 'id' => 'TZ-1988-00123'],
            ['first' => 'Aisha', 'last' => 'Said', 'phone' => '+255712345679', 'id' => 'TZ-1992-00456'],
            ['first' => 'David', 'last' => 'Smith', 'phone' => '+14155550199', 'id' => 'US-PASSPORT-9988'],
            ['first' => 'Elena', 'last' => 'Rossi', 'phone' => '+39066981234', 'id' => 'IT-PASSPORT-4433'],
            ['first' => 'Hamisi', 'last' => 'Khalfan', 'phone' => '+255754112233', 'id' => 'TZ-1985-00789'],
            ['first' => 'Neema', 'last' => 'John', 'phone' => '+255768998877', 'id' => 'TZ-1995-00999'],
            ['first' => 'Liam', 'last' => 'Johnson', 'phone' => '+442079460011', 'id' => 'UK-PASSPORT-1122'],
            ['first' => 'Fatma', 'last' => 'Abdullah', 'phone' => '+255777332211', 'id' => 'TZ-1990-00334'],
        ];

        foreach (range(1, 12) as $index) {
            $voyage = $voyages->random();
            $classes = FerryClass::where('ferry_id', $voyage->ferry_id)->where('status', 'active')->get();
            if ($classes->isEmpty()) continue;

            $ferryClass = $classes->random();
            $fareObj = \App\Models\Fare::where('route_id', $voyage->route_id)
                ->where('ferry_class_id', $ferryClass->id)
                ->where('status', 'active')
                ->first();

            $isResident = $index % 3 !== 0; // 2/3 residents (TZS), 1/3 foreigners (USD)
            $currency = $isResident ? 'TZS' : 'USD';
            $unitFare = $fareObj ? ($isResident ? (float) $fareObj->adult_price : (float) $fareObj->adult_price_usd) : ($isResident ? 50000.0 : 35.0);

            $paxCount = ($index % 4) + 1;
            $totalAmount = round($unitFare * $paxCount, 2);

            $status = match ($index % 5) {
                0 => 'held',
                1 => 'pending_payment',
                default => 'confirmed'
            };

            $ref = 'FB-' . mt_rand(100000, 999999);
            while (Booking::where('booking_reference', $ref)->exists()) {
                $ref = 'FB-' . mt_rand(100000, 999999);
            }

            $booking = Booking::create([
                'booking_reference' => $ref,
                'user_id' => $admin->id,
                'booked_by_user_id' => $admin->id,
                'status' => $status,
                'total_amount' => $totalAmount,
                'discount_amount' => 0,
                'net_amount' => $totalAmount,
                'payment_channel' => $status === 'confirmed' ? ($isResident ? 'M-Pesa / TigoPesa' : 'Visa / MasterCard') : null,
                'transaction_reference' => $status === 'confirmed' ? 'TX-' . mt_rand(100000000, 999999999) : null,
                'notes' => "Simulated onboarding booking ({$currency} billing).",
                'currency' => $currency,
                'total_amount_tzs' => $currency === 'TZS' ? $totalAmount : round($totalAmount * 2600, 2),
                'total_amount_usd' => $currency === 'USD' ? $totalAmount : round($totalAmount / 2600, 2),
                'exchange_rate' => 2600.0,
            ]);

            for ($i = 0; $i < $paxCount; $i++) {
                $paxInfo = $names[array_rand($names)];
                $pax = Passenger::create([
                    'first_name' => $paxInfo['first'],
                    'last_name' => $paxInfo['last'] . ' ' . ($index . '-' . $i),
                    'gender' => ($i % 2 === 0) ? 'male' : 'female',
                    'phone' => $paxInfo['phone'],
                    'id_type_id' => $isResident ? 1 : 2,
                    'id_number' => $paxInfo['id'] . '-' . $index . $i,
                    'nationality' => $isResident ? 'Tanzania' : 'United States',
                ]);

                $tNum = 'TK-' . mt_rand(1000000000, 9999999999);
                while (Ticket::where('ticket_number', $tNum)->exists()) {
                    $tNum = 'TK-' . mt_rand(1000000000, 9999999999);
                }

                $seat = $voyage->ferry && $voyage->ferry->has_layout
                    ? strtoupper(substr($ferryClass->name, 0, 1)) . '-' . ($i + 1) . mt_rand(1, 9)
                    : 'POOL-' . ($i + 1);

                Ticket::create([
                    'booking_id' => $booking->id,
                    'ticket_number' => $tNum,
                    'passenger_id' => $pax->id,
                    'voyage_id' => $voyage->id,
                    'ferry_class_id' => $ferryClass->id,
                    'seat_number' => $seat,
                    'base_fare' => $unitFare,
                    'discount_amount' => 0,
                    'final_fare' => $unitFare,
                    'status' => $status === 'confirmed' ? 'confirmed' : 'reserved',
                    'print_count' => ($status === 'confirmed' && $index % 2 === 0) ? 1 : 0,
                    'last_printed_at' => ($status === 'confirmed' && $index % 2 === 0) ? now()->subHours(2) : null,
                    'currency' => $currency,
                    'nationality_type' => $isResident ? 1 : 2,
                    'qr_code_token' => \Illuminate\Support\Str::uuid()->toString(),
                ]);
            }

            if ($status === 'confirmed') {
                $pmId = $isResident
                    ? (\App\Models\PaymentMethod::where('slug', 'mpesa')->value('id') ?? 4)
                    : (\App\Models\PaymentMethod::where('slug', 'visa_mastercard')->value('id') ?? 8);

                \App\Models\SystemTransaction::create([
                    'transaction_number' => 'TX-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'transaction_category_id' => 2, // ticket-sale
                    'transaction_status_id' => 4, // completed
                    'payment_type_id' => 2, // Credit
                    'amount' => $totalAmount,
                    'currency_code' => $currency,
                    'exchange_rate' => $currency === 'TZS' ? 1.000000 : 2600.000000,
                    'amount_tzs' => $currency === 'TZS' ? $totalAmount : round($totalAmount * 2600, 2),
                    'payment_method_id' => $pmId,
                    'payment_reference' => 'REF-' . mt_rand(10000000, 99999999),
                    'booking_id' => $booking->id,
                    'user_id' => $admin->id,
                    'description' => "Payment for simulated booking #{$booking->booking_reference}",
                ]);
            }

            BookingLog::create([
                'booking_id' => $booking->id,
                'action' => 'Booking Created',
                'description' => "Booking created via seeder with {$paxCount} passenger(s) ({$currency})",
                'performed_by_user_id' => $admin->id,
            ]);
        }
    }
}
