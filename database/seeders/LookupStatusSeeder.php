<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentStatus;
use App\Models\PaymentMethod;

class LookupStatusSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Booking Statuses (table may exist from inherited schema)
        if (Schema::hasTable('booking_statuses')) {
            $bookingStatuses = [
                ['slug' => 'draft', 'name' => 'Draft', 'color' => 'slate', 'is_active' => true],
                ['slug' => 'held', 'name' => 'Held (Temporary Lock)', 'color' => 'blue', 'is_active' => true],
                ['slug' => 'pending_payment', 'name' => 'Pending Payment', 'color' => 'amber', 'is_active' => true],
                ['slug' => 'confirmed', 'name' => 'Confirmed & Paid', 'color' => 'green', 'is_active' => true],
                ['slug' => 'cancelled', 'name' => 'Cancelled', 'color' => 'red', 'is_active' => true],
                ['slug' => 'expired', 'name' => 'Expired Hold', 'color' => 'slate', 'is_active' => true],
            ];

            foreach ($bookingStatuses as $bs) {
                DB::table('booking_statuses')->updateOrInsert(['slug' => $bs['slug']], $bs);
            }
        }

        // 2. Booking Sources (table may exist from inherited schema)
        if (Schema::hasTable('booking_sources')) {
            $bookingSources = [
                ['slug' => 'public_portal', 'name' => 'Public Portal (Customer)', 'is_active' => true],
                ['slug' => 'agent_portal', 'name' => 'Agent Portal (Sales Partner)', 'is_active' => true],
                ['slug' => 'pos_terminal', 'name' => 'POS Terminal (Window Ticket)', 'is_active' => true],
                ['slug' => 'mobile_app', 'name' => 'Mobile App (iOS/Android)', 'is_active' => true],
                ['slug' => 'api', 'name' => 'B2B API Integration', 'is_active' => true],
            ];

            foreach ($bookingSources as $src) {
                DB::table('booking_sources')->updateOrInsert(['slug' => $src['slug']], $src);
            }
        }

        // 3. Ticket Statuses (table may exist from inherited schema)
        if (Schema::hasTable('ticket_statuses')) {
            $ticketStatuses = [
                ['slug' => 'reserved', 'name' => 'Reserved', 'color' => 'blue', 'is_active' => true],
                ['slug' => 'confirmed', 'name' => 'Confirmed / Valid', 'color' => 'green', 'is_active' => true],
                ['slug' => 'checked_in', 'name' => 'Checked In (Pre-Boarding)', 'color' => 'indigo', 'is_active' => true],
                ['slug' => 'boarded', 'name' => 'Boarded / Traveled', 'color' => 'purple', 'is_active' => true],
                ['slug' => 'cancelled', 'name' => 'Cancelled Ticket', 'color' => 'red', 'is_active' => true],
                ['slug' => 'no_show', 'name' => 'No Show', 'color' => 'slate', 'is_active' => true],
            ];

            foreach ($ticketStatuses as $ts) {
                DB::table('ticket_statuses')->updateOrInsert(['slug' => $ts['slug']], $ts);
            }
        }

        // 4. Payment Statuses
        $paymentStatuses = [
            ['slug' => 'unpaid', 'name' => 'Unpaid', 'color' => 'red', 'is_active' => true],
            ['slug' => 'partially_paid', 'name' => 'Partially Paid', 'color' => 'amber', 'is_active' => true],
            ['slug' => 'paid', 'name' => 'Paid in Full', 'color' => 'green', 'is_active' => true],
            ['slug' => 'refunded', 'name' => 'Refunded', 'color' => 'slate', 'is_active' => true],
            ['slug' => 'failed', 'name' => 'Failed Transaction', 'color' => 'red', 'is_active' => true],
        ];

        foreach ($paymentStatuses as $ps) {
            PaymentStatus::updateOrCreate(['slug' => $ps['slug']], $ps);
        }

        // 5. Payment Methods
        $paymentMethods = [
            ['slug' => 'cash', 'name' => 'Cash at Window', 'is_active' => true],
            ['slug' => 'crdb_bank', 'name' => 'CRDB Bank Gateway', 'is_active' => true],
            ['slug' => 'nmb_bank', 'name' => 'NMB Bank Gateway', 'is_active' => true],
            ['slug' => 'mpesa', 'name' => 'Vodacom M-Pesa', 'is_active' => true],
            ['slug' => 'tigopesa', 'name' => 'Tigo Pesa', 'is_active' => true],
            ['slug' => 'airtel_money', 'name' => 'Airtel Money', 'is_active' => true],
            ['slug' => 'halopesa', 'name' => 'Halopesa', 'is_active' => true],
            ['slug' => 'visa_mastercard', 'name' => 'Visa / Mastercard (Stripe/CyberSource)', 'is_active' => true],
        ];

        foreach ($paymentMethods as $pm) {
            PaymentMethod::updateOrCreate(['slug' => $pm['slug']], $pm);
        }

        // 6. Transaction Categories
        $transactionCategories = [
            ['id' => 1, 'name' => 'Float Top-up', 'slug' => 'float-top-up', 'type' => 'credit', 'color' => 'green'],
            ['id' => 2, 'name' => 'Ticket Sale', 'slug' => 'ticket-sale', 'type' => 'debit', 'color' => 'blue'],
            ['id' => 3, 'name' => 'Commission', 'slug' => 'commission', 'type' => 'credit', 'color' => 'purple'],
            ['id' => 4, 'name' => 'Reversal', 'slug' => 'reversal', 'type' => 'credit', 'color' => 'yellow'],
            ['id' => 5, 'name' => 'Refund', 'slug' => 'refund', 'type' => 'debit', 'color' => 'orange'],
            ['id' => 6, 'name' => 'Adjustment (Credit)', 'slug' => 'adjustment-credit', 'type' => 'credit', 'color' => 'gray'],
            ['id' => 7, 'name' => 'Adjustment (Debit)', 'slug' => 'adjustment-debit', 'type' => 'debit', 'color' => 'gray'],
            ['id' => 8, 'name' => 'Cancellation Penalty', 'slug' => 'cancellation-penalty', 'type' => 'credit', 'color' => 'amber'],
        ];

        foreach ($transactionCategories as $cat) {
            DB::table('transaction_categories')->updateOrInsert(['slug' => $cat['slug']], [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'type' => $cat['type'],
                'color' => $cat['color'],
                'updated_at' => now(),
            ]);
        }
    }
}
