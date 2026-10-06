<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1. Assign cargo permissions to portal-facing roles
        // ═══════════════════════════════════════════════════════════

        $cargoPermissions = DB::table('permissions')
            ->where('name', 'like', 'cargo-%')
            ->pluck('id', 'name');

        $roleIds = DB::table('roles')->pluck('id', 'slug');

        // Define which roles get which cargo permissions
        $assignments = [
            // super_admin gets everything
            'super_admin' => $cargoPermissions->keys()->toArray(),

            // admin gets everything
            'admin' => $cargoPermissions->keys()->toArray(),

            // sales_officer — can book, view, cancel
            'sales_officer' => [
                'cargo-book', 'cargo-view', 'cargo-cancel',
            ],

            // booking_officer — same as sales officer
            'booking_officer' => [
                'cargo-book', 'cargo-view', 'cargo-cancel',
            ],

            // counter_staff — can book and view
            'counter_staff' => [
                'cargo-book', 'cargo-view',
            ],

            // agent — can book, view, cancel (via portal)
            'agent' => [
                'cargo-book', 'cargo-view', 'cargo-cancel',
            ],

            // associate_agent — can book and view
            'associate_agent' => [
                'cargo-book', 'cargo-view',
            ],

            // finance_officer — can view reports
            'finance_officer' => [
                'cargo-view', 'cargo-report', 'cargo-export-report',
            ],
        ];

        $inserted = 0;
        foreach ($assignments as $roleSlug => $permNames) {
            $roleId = $roleIds[$roleSlug] ?? null;
            if (!$roleId) continue;

            foreach ($permNames as $permName) {
                $permId = $cargoPermissions[$permName] ?? null;
                if (!$permId) continue;

                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                    $inserted++;
                }
            }
        }

        $this->command->info("Cargo permissions: {$inserted} new role-permission assignments created.");

        // ═══════════════════════════════════════════════════════════
        // 2. Assign fleet/route management permissions to admin roles
        // ═══════════════════════════════════════════════════════════

        $fleetPermissions = DB::table('permissions')
            ->whereIn('name', [
                'manage-routes', 'manage-stations',
                'manage-transport-units', 'manage-schedules', 'manage-services',
            ])
            ->pluck('id', 'name');

        $adminRoles = ['super_admin', 'admin'];
        $fleetInserted = 0;

        foreach ($adminRoles as $roleSlug) {
            $roleId = $roleIds[$roleSlug] ?? null;
            if (!$roleId) continue;

            foreach ($fleetPermissions as $permName => $permId) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                    $fleetInserted++;
                }
            }
        }

        $this->command->info("Fleet/route permissions: {$fleetInserted} new role-permission assignments created.");
    }
}
