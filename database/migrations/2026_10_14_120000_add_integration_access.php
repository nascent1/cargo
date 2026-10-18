<?php

use App\Models\DualControlConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Settings › Integrations (SMS gateways, SMTP servers) and the delivery log:
 * permissions (granted to super_admin and admin), sidebar entries and the dual-control rules
 * from config/dual_control.php. The Announcements entry is switched off: that screen has no
 * model or table in this platform.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'integration:read'   => 'View Integrations',
        'integration:create' => 'Add Integrations',
        'integration:update' => 'Edit & Test Integrations',
        'integration:delete' => 'Delete Integrations',
    ];

    private const MENUS = [
        ['Integrations', 'admin.settings.integrations.index', 'fa-plug', 'integration:read'],
        ['Delivery Log', 'admin.notifications.logs', 'fa-paper-plane', 'notification:read'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $label, 'module' => 'integration', 'description' => $label,
                'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'admin'])->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        $administration = DB::table('menus')->where('title', 'Administration')->whereNull('parent_id')->value('id');
        foreach (self::MENUS as [$title, $route, $icon, $permission]) {
            if ($administration && !DB::table('menus')->where('route', $route)->exists()) {
                DB::table('menus')->insert([
                    'title' => $title, 'route' => $route, 'icon' => $icon, 'parent_id' => $administration,
                    'permission_name' => $permission, 'section' => 'administration',
                    'sort_order' => (int) DB::table('menus')->where('parent_id', $administration)->max('sort_order') + 1,
                    'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        DB::table('menus')->where('route', 'admin.notifications.announcements')->update(['is_active' => false, 'updated_at' => $now]);

        DualControlConfig::syncFromRegistry();
    }

    public function down(): void
    {
        DB::table('dual_control_configs')->where('operation_type', 'like', 'integration_%')->delete();
        DB::table('menus')->where('route', 'admin.notifications.announcements')->update(['is_active' => true]);
        DB::table('menus')->whereIn('route', array_column(self::MENUS, 1))->delete();

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
