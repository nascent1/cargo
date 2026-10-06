<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CargoPermissionsTest extends TestCase
{
    /**
     * Test that cargo permissions are assigned to expected roles.
     */
    public function test_cargo_permissions_assigned_to_roles(): void
    {
        $rolePermissions = DB::table('role_permissions')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.name', 'like', 'cargo-%')
            ->select('roles.slug as role', 'permissions.name as permission')
            ->get()
            ->groupBy('role')
            ->map(fn($group) => $group->pluck('permission')->toArray());

        // Admin should have ALL cargo permissions
        $this->assertTrue($rolePermissions->has('admin'), 'Admin role should have cargo permissions');
        $this->assertContains('cargo-book', $rolePermissions['admin']);
        $this->assertContains('cargo-view', $rolePermissions['admin']);
        $this->assertContains('cargo-cancel', $rolePermissions['admin']);
        $this->assertContains('cargo-manage-types', $rolePermissions['admin']);
        $this->assertContains('cargo-manage-fares', $rolePermissions['admin']);
        $this->assertContains('cargo-manage-capacity', $rolePermissions['admin']);
        $this->assertContains('cargo-report', $rolePermissions['admin']);

        // Agent should have book, view, cancel
        $this->assertTrue($rolePermissions->has('agent'), 'Agent role should have cargo permissions');
        $this->assertContains('cargo-book', $rolePermissions['agent']);
        $this->assertContains('cargo-view', $rolePermissions['agent']);
        $this->assertContains('cargo-cancel', $rolePermissions['agent']);

        // Sales officer should have book, view, cancel
        $this->assertTrue($rolePermissions->has('sales_officer'), 'Sales officer should have cargo permissions');
        $this->assertContains('cargo-book', $rolePermissions['sales_officer']);
        $this->assertContains('cargo-view', $rolePermissions['sales_officer']);

        // Finance officer should have view, report, export
        $this->assertTrue($rolePermissions->has('finance_officer'), 'Finance officer should have cargo permissions');
        $this->assertContains('cargo-view', $rolePermissions['finance_officer']);
        $this->assertContains('cargo-report', $rolePermissions['finance_officer']);
        $this->assertContains('cargo-export-report', $rolePermissions['finance_officer']);
    }

    /**
     * Test fleet/route permissions are assigned to admin roles.
     */
    public function test_fleet_permissions_assigned_to_admin_roles(): void
    {
        $fleetPermissions = ['manage-routes', 'manage-stations', 'manage-transport-units', 'manage-schedules', 'manage-services'];

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

        foreach ($fleetPermissions as $permName) {
            $permId = DB::table('permissions')->where('name', $permName)->value('id');
            $this->assertNotNull($permId, "Permission '{$permName}' should exist");

            // Admin role
            $this->assertTrue(
                DB::table('role_permissions')->where('role_id', $adminRoleId)->where('permission_id', $permId)->exists(),
                "Admin should have '{$permName}'"
            );

            // Super admin role
            $this->assertTrue(
                DB::table('role_permissions')->where('role_id', $superAdminRoleId)->where('permission_id', $permId)->exists(),
                "Super admin should have '{$permName}'"
            );
        }
    }

    /**
     * Test User::hasPermission() method for agent.
     */
    public function test_agent_has_permission_cargo_book(): void
    {
        $agent = User::where('domain', 'agent')->first();
        if (!$agent) {
            $this->markTestSkipped('No agent user in DB');
        }

        $this->assertTrue($agent->hasPermission('cargo-book'), 'Agent should have cargo-book permission');
        $this->assertTrue($agent->hasPermission('cargo-view'), 'Agent should have cargo-view permission');
        $this->assertTrue($agent->hasPermission('portal:access'), 'Agent should have portal:access');
    }
}
