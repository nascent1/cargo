<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Create admin user
        $user1 = \App\Models\User::updateOrCreate(
            ['email' => 'admin@ferrybooking.co.tz'],
            [
                'phone' => '+255700000001',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('Admin@123'),
                'domain' => 'system_user',
                'status' => 'active',
            ]
        );

        \App\Models\SystemUser::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'employee_id' => 'EMP0001',
                'department' => 'Administration',
                'position' => 'System Administrator',
                'hire_date' => $now->toDateString(),
                'status' => 'active',
            ]
        );

        $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');
        if ($superAdminRoleId && !DB::table('user_roles')->where('user_id', $user1->id)->where('role_id', $superAdminRoleId)->exists()) {
            DB::table('user_roles')->insert([
                'user_id' => $user1->id,
                'role_id' => $superAdminRoleId,
                'assigned_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create a supervisor / checker for dual control testing
        $user2 = \App\Models\User::updateOrCreate(
            ['email' => 'supervisor@ferrybooking.co.tz'],
            [
                'phone' => '+255700000099',
                'first_name' => 'Operations',
                'last_name' => 'Supervisor',
                'password' => Hash::make('Admin@123'),
                'domain' => 'system_user',
                'status' => 'active',
            ]
        );

        \App\Models\SystemUser::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'employee_id' => 'EMP0099',
                'department' => 'Operations',
                'position' => 'Operations Supervisor',
                'hire_date' => $now->toDateString(),
                'status' => 'active',
            ]
        );

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminRoleId && !DB::table('user_roles')->where('user_id', $user2->id)->where('role_id', $adminRoleId)->exists()) {
            DB::table('user_roles')->insert([
                'user_id' => $user2->id,
                'role_id' => $adminRoleId,
                'assigned_by' => $user1->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
