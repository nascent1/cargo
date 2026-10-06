<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\AssociateAgent;
use App\Models\SystemTransaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    public function run(): void
    {

        $now = now();
        // 1. Direct Partner Agency (e.g. Zanzibar Express Travel Ltd)
        $user1 = User::firstOrCreate(['email' => 'partner@zanzibarexpress.co.tz'], [
            'first_name' => 'Rashid',
            'last_name'  => 'Suleiman',
            'phone'      => '+255712345678',
            'password'   => Hash::make('password'),
            'domain'     => 'agent',
            'status'     => 'active',
            'is_system_account' => false,
        ]);
        $user1->assignRole('agent');

        $agent1 = Agent::firstOrCreate(['agent_code' => 'ZEX-001'], [
            'user_id'         => $user1->id,
            'agency_name'     => 'Zanzibar Express Travel Ltd',
            'contact_mobile'  => '+255 712 345 678',
            'email'           => 'partner@zanzibarexpress.co.tz',
            'address'         => 'Stone Town Ferry Terminal, Zanzibar',
            'agent_type'      => 'direct',
            'is_active'       => true,
            'commission_rate' => 5.00,
        ]);

        $account1 = AgentAccount::firstOrCreate(['agent_id' => $agent1->id], [
            'account_number'  => 'ACC-ZEX-001',
            'currency'        => 'TZS',
            'balance'         => 4500000.0000,
            'credit_limit'    => 2000000.0000,
            'is_active'       => true,
        ]);

        // Associate staff for Direct Agency
        $assocUser1 = User::firstOrCreate(['email' => 'staff1@zanzibarexpress.co.tz'], [
            'first_name' => 'Amina',
            'last_name'  => 'Hassan',
            'phone'      => '+255713000111',
            'password'   => Hash::make('password'),
            'domain'     => 'associate_agent',
            'status'     => 'active',
            'is_system_account' => false,
        ]);
        $assocUser1->assignRole('associate_agent');

        AssociateAgent::firstOrCreate(['user_id' => $assocUser1->id], [
            'agent_id'           => $agent1->id,
            'associate_name'     => 'Amina Hassan',
            'contact_mobile'     => '+255 713 000 111',
            'email'              => 'staff1@zanzibarexpress.co.tz',
            'daily_credit_limit' => 1000000.0000,
            'is_active'          => true,
        ]);

        // 2. Reseller Agency (e.g. Dar Coastal Ticketing Agency)
        $user2 = User::firstOrCreate(['email' => 'sales@darcoastal.co.tz'], [
            'first_name' => 'Juma',
            'last_name'  => 'Kassim',
            'phone'      => '+255754999888',
            'password'   => Hash::make('password'),
            'domain'     => 'agent',
            'status'     => 'active',
            'is_system_account' => false,
        ]);
        $user2->assignRole('agent');

        $agent2 = Agent::firstOrCreate(['agent_code' => 'DCT-002'], [
            'user_id'         => $user2->id,
            'agency_name'     => 'Dar Coastal Ticketing Agency',
            'contact_mobile'  => '+255 754 999 888',
            'email'           => 'sales@darcoastal.co.tz',
            'address'         => 'Sokoine Drive, Dar es Salaam',
            'agent_type'      => 'reseller',
            'is_active'       => true,
            'commission_rate' => 3.50,
        ]);

        $account2 = AgentAccount::firstOrCreate(['agent_id' => $agent2->id], [
            'account_number'  => 'ACC-DCT-002',
            'currency'        => 'TZS',
            'balance'         => 1250000.0000,
            'credit_limit'    => 500000.0000,
            'is_active'       => true,
        ]);

        // Seed sample transactions for Agency 1
        SystemTransaction::firstOrCreate(['transaction_number' => 'TOPUP-20260701-001'], [
            'transaction_category_id' => 1, // float-top-up
            'transaction_status_id'   => 4, // completed
            'payment_type_id'         => 2, // Credit
            'amount'                  => 5000000.00,
            'currency_code'           => 'TZS',
            'exchange_rate'           => 1.0,
            'amount_tzs'              => 5000000.00,
            'balance_before'          => 0.00,
            'balance_after'           => 5000000.00,
            'payment_reference'       => 'CRDB-TRX-88231',
            'user_id'                 => $user1->id,
            'description'             => 'Initial float deposit via CRDB Bank Transfer',
        ]);

        SystemTransaction::firstOrCreate(['transaction_number' => 'TX-20260715-A01'], [
            'transaction_category_id' => 2, // ticket-sale
            'transaction_status_id'   => 4, // completed
            'payment_type_id'         => 1, // Debit
            'amount'                  => 500000.00,
            'currency_code'           => 'TZS',
            'exchange_rate'           => 1.0,
            'amount_tzs'              => 500000.00,
            'balance_before'          => 5000000.00,
            'balance_after'           => 4500000.00,
            'payment_reference'       => '-',
            'user_id'                 => $user1->id,
            'description'             => 'Float deduction for group booking #HC-2026-9901',
        ]);

        // Assign all active transport units to seeded agencies by default
        $unitIds = \App\Models\TransportUnit::where('status', 'active')->pluck('id')->toArray();
        if (!empty($unitIds)) {
            foreach (Agent::all() as $ag) {
                // agent_ferries pivot still exists in inherited schema
                if (method_exists($ag, 'ferries')) {
                    $ag->ferries()->syncWithoutDetaching($unitIds);
                }
            }
        }


        // Create a supervisor / checker for dual control testing
        $user21 = \App\Models\User::updateOrCreate(
            ['email' => 'officer@ferrybooking.co.tz'],
            [
                'phone' => '+255700000199',
                'first_name' => 'Operations',
                'last_name' => 'Supervisor',
                'password' => Hash::make('password'),
                'domain' => 'system_user',
                'status' => 'active',
            ]
        );

        \App\Models\SystemUser::updateOrCreate(
            ['user_id' => $user21->id],
            [
                'employee_id' => 'EMP009911',
                'department' => 'Operations',
                'position' => 'Sales Officer',
                'hire_date' => $now->toDateString(),
                'status' => 'active',
            ]
        );
        $user21->assignRole('sales_officer');
        
    }
}
