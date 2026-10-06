<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SystemSalesChannelsSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            [
                'email' => 'system.web@victoriaferries.com',
                'first_name' => 'Web Portal',
                'last_name' => 'Online Channel',
                'phone' => 'SYS-WEB-001',
                'domain' => 'system_user',
                'status' => 'active',
                'is_system_account' => true,
                'password' => Hash::make('Channel-Secret-Web-2026!'),
            ],
            [
                'email' => 'system.app@victoriaferries.com',
                'first_name' => 'Mobile App',
                'last_name' => 'Booking Channel',
                'phone' => 'SYS-APP-001',
                'domain' => 'system_user',
                'status' => 'active',
                'is_system_account' => true,
                'password' => Hash::make('Channel-Secret-App-2026!'),
            ],
            [
                'email' => 'system.api@victoriaferries.com',
                'first_name' => 'B2B API',
                'last_name' => 'Gateway Channel',
                'phone' => 'SYS-API-001',
                'domain' => 'system_user',
                'status' => 'active',
                'is_system_account' => true,
                'password' => Hash::make('Channel-Secret-Api-2026!'),
            ],
        ];

        foreach ($channels as $c) {
            User::updateOrCreate(['email' => $c['email']], $c);
        }
    }
}
