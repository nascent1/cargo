<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->truncate();

        // 1. Main Navigation
        $mainMenu = [
            ['title' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'fa-chart-pie', 'permission_name' => null, 'section' => 'main'],
            ['title' => 'Dual Control Inbox', 'route' => 'admin.dual-control.index', 'icon' => 'fa-check-double', 'permission_name' => 'dc:list', 'section' => 'main'],
        ];

        foreach ($mainMenu as $index => $item) {
            Menu::create(array_merge($item, ['sort_order' => $index + 1]));
        }

        // 2. Ferry Operations Parent
        $opsParent = Menu::create([
            'title' => 'Operations',
            'route' => null,
            'icon' => 'fa-ship',
            'permission_name' => null,
            'section' => 'main',
            'sort_order' => 100,
        ]);

        $opsItems = [
            ['title' => 'Cargo Booking', 'route' => 'admin.cargo.booking', 'icon' => 'fa-ticket-alt', 'permission_name' => 'cargo-create'],
            ['title' => 'Consignments', 'route' => 'admin.cargo.consignments', 'icon' => 'fa-box', 'permission_name' => 'cargo-view'],
            ['title' => 'Loading Manifests', 'route' => 'admin.cargo.manifest', 'icon' => 'fa-clipboard-list', 'permission_name' => 'cargo-manifest'],
            ['title' => 'Cargo Types', 'route' => 'admin.cargo.types', 'icon' => 'fa-tags', 'permission_name' => 'cargo-manage-types'],
            ['title' => 'Cargo Fares', 'route' => 'admin.cargo.fares', 'icon' => 'fa-money-bill', 'permission_name' => 'cargo-manage-fares'],
            ['title' => 'Stations', 'route' => 'admin.cargo.stations.index', 'icon' => 'fa-anchor', 'permission_name' => 'station:list'],
            ['title' => 'Routes', 'route' => 'admin.cargo.routes.index', 'icon' => 'fa-route', 'permission_name' => 'route:list'],
            ['title' => 'Transport Units', 'route' => 'admin.cargo.transport-units.index', 'icon' => 'fa-truck', 'permission_name' => 'ferry:list'],
            ['title' => 'Schedules', 'route' => 'admin.cargo.schedules.index', 'icon' => 'fa-calendar-alt', 'permission_name' => 'schedule:list'],
            ['title' => 'Journeys', 'route' => 'admin.cargo.journeys.index', 'icon' => 'fa-compass', 'permission_name' => 'voyage:list'],
        ];

        foreach ($opsItems as $index => $item) {
            Menu::create(array_merge($item, [
                'parent_id' => $opsParent->id,
                'section' => 'main',
                'sort_order' => $index + 1,
            ]));
        }

        // 3. Administration Parent
        $adminParent = Menu::create([
            'title' => 'Administration',
            'route' => null,
            'icon' => 'fa-cogs',
            'permission_name' => null, // Display logic will handle child permissions
            'section' => 'administration',
            'sort_order' => 200,
        ]);

        $adminItems = [
            ['title' => 'Users', 'route' => 'admin.users.index', 'icon' => 'fa-users-cog', 'permission_name' => 'user:list'],
            ['title' => 'Agencies & Float', 'route' => 'admin.agents.index', 'icon' => 'fa-store-alt', 'permission_name' => 'user:list'],
            ['title' => 'Audit Logs', 'route' => 'admin.audit.index', 'icon' => 'fa-clipboard-list', 'permission_name' => 'audit:read'],
            ['title' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'fa-cog', 'permission_name' => 'setting:read'],
            ['title' => 'Exchange Rates', 'route' => 'admin.settings.exchange-rates.index', 'icon' => 'fa-money-bill-wave', 'permission_name' => 'setting:read'],
            ['title' => 'Lookups & Statuses', 'route' => 'admin.settings.lookups.index', 'icon' => 'fa-list-check', 'permission_name' => 'setting:read'],
            ['title' => 'Dual Control Rules', 'route' => 'admin.settings.dual-control-configs.index', 'icon' => 'fa-shield-alt', 'permission_name' => 'setting:read'],
            ['title' => 'Menus', 'route' => 'admin.settings.menus.index', 'icon' => 'fa-list', 'permission_name' => 'setting:read'],
            ['title' => 'Notifications', 'route' => 'admin.notifications.templates', 'icon' => 'fa-bell', 'permission_name' => 'notification:read'],
            ['title' => 'Announcements', 'route' => 'admin.notifications.announcements', 'icon' => 'fa-bullhorn', 'permission_name' => 'notification:read'],
        ];

        foreach ($adminItems as $index => $item) {
            Menu::create(array_merge($item, [
                'parent_id' => $adminParent->id,
                'section' => 'administration',
                'sort_order' => $index + 1,
            ]));
        }
    }
}
