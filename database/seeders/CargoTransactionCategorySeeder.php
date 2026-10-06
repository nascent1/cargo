<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoTransactionCategorySeeder extends Seeder
{
    /**
     * Seed cargo-specific transaction categories used for
     * financial reporting and ledger classification.
     */
    public function run(): void
    {
        // Reset the PG sequence to the max id + 1 (fixes conflict when
        // previous seeders insert with explicit IDs)
        $maxId = DB::table('transaction_categories')->max('id') ?? 0;
        DB::statement("SELECT setval('transaction_categories_id_seq', ?, true)", [$maxId]);

        $categories = [
            ['slug' => 'cargo-booking', 'name' => 'Cargo Booking Revenue', 'type' => 'debit', 'color' => 'teal'],
            ['slug' => 'cargo-surcharge', 'name' => 'Cargo Surcharges', 'type' => 'debit', 'color' => 'cyan'],
            ['slug' => 'cargo-storage', 'name' => 'Storage Fees', 'type' => 'debit', 'color' => 'blue'],
            ['slug' => 'cargo-insurance', 'name' => 'Cargo Insurance', 'type' => 'debit', 'color' => 'indigo'],
            ['slug' => 'cargo-refund', 'name' => 'Cargo Booking Refund', 'type' => 'credit', 'color' => 'orange'],
            ['slug' => 'cargo-partial-refund', 'name' => 'Cargo Partial Refund', 'type' => 'credit', 'color' => 'amber'],
            ['slug' => 'cargo-agent-commission', 'name' => 'Agent Cargo Commission', 'type' => 'credit', 'color' => 'purple'],
            ['slug' => 'cargo-cancellation-fee', 'name' => 'Cargo Cancellation Fee', 'type' => 'credit', 'color' => 'red'],
        ];

        foreach ($categories as $category) {
            $exists = DB::table('transaction_categories')->where('slug', $category['slug'])->exists();
            if (!$exists) {
                DB::table('transaction_categories')->insert(array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
