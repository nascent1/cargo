<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportFilterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'report_filter:agents', 'display_name' => 'Filter by Agents', 'module' => 'report', 'description' => 'Can filter reports by any agent', 'is_system' => false],
            ['name' => 'report_filter:routes', 'display_name' => 'Filter by Routes', 'module' => 'report', 'description' => 'Can filter reports by any route', 'is_system' => false],
            ['name' => 'report_filter:ferries', 'display_name' => 'Filter by Ferries', 'module' => 'report', 'description' => 'Can filter reports by any ferry', 'is_system' => false],
            ['name' => 'report_filter:associates', 'display_name' => 'Filter by Associates', 'module' => 'report', 'description' => 'Can filter reports by associates', 'is_system' => false],
        ];

        foreach ($permissions as $p) {
            \App\Models\Permission::firstOrCreate(['name' => $p['name']], $p);
        }

        $superAdmin = \App\Models\Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching(\App\Models\Permission::where('name', 'like', 'report_filter:%')->pluck('id'));
        }
        $salesOfficer = \App\Models\Role::where('slug', 'sales_officer')->first();
        if ($salesOfficer) {
            $salesOfficer->permissions()->syncWithoutDetaching(\App\Models\Permission::where('name', 'like', 'report_filter:%')->pluck('id'));
        }
        $agent = \App\Models\Role::where('slug', 'agent')->first();
        if ($agent) {
            $agent->permissions()->syncWithoutDetaching(\App\Models\Permission::where('name', 'report_filter:associates')->pluck('id'));
        }
    }
}
