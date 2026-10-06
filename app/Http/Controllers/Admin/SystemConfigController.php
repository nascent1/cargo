<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * General settings tab (system_configs table).
     */
    public function index()
    {
        $configs = SystemConfig::orderBy('group')->orderBy('key_name')->get()->groupBy('group');
        return view('admin.settings.index', compact('configs'));
    }

    /**
     * Save general settings.
     */
    public function update(Request $request, \App\Services\DualControlService $dualControl)
    {
        $validated = $request->validate(['settings' => 'required|array']);
        $dualControlCount = 0;
        $directUpdateCount = 0;

        foreach ($validated['settings'] as $key => $value) {
            $config = SystemConfig::where('key_name', $key)->first();
            
            // Special case: if saving usd_to_tzs_exchange_rate (even if unchanged), update the timestamp
            if ($key === 'usd_to_tzs_exchange_rate') {
                $lastUpdatedConfig = SystemConfig::where('key_name', 'exchange_rate_last_updated')->first();
                if (!$lastUpdatedConfig || $lastUpdatedConfig->value !== today()->toDateString()) {
                    // Update the timestamp directly to prevent the admin from being locked out
                    // while waiting for dual control approval of the rate change itself.
                    SystemConfig::updateOrCreate(
                        ['key_name' => 'exchange_rate_last_updated'],
                        [
                            'display_name' => 'Exchange Rate Last Updated',
                            'value' => today()->toDateString(),
                            'group' => 'financial',
                            'data_type' => 'date',
                            'description' => 'Last time the exchange rate was updated.'
                        ]
                    );
                }
            }

            if ($config && $config->value !== $value) {
                $operationType = ($key === 'commission_default_method') ? 'commission_method_update' : 'setting_update';
                $dcReq = $dualControl->submit($operationType, 'system_config', $config->id, [
                    'before' => ['value' => $config->value, 'key_name' => $key],
                    'after'  => ['value' => $value, 'key_name' => $key],
                ]);

                if ($dcReq->status === 'pending') {
                    $dualControlCount++;
                } else {
                    $directUpdateCount++;
                }
            }
        }

        if ($dualControlCount > 0 && $directUpdateCount > 0) {
            $message = "Settings processed: {$directUpdateCount} updated immediately, {$dualControlCount} submitted for Maker-Checker Dual Control approval.";
        } elseif ($dualControlCount > 0) {
            $message = "Configuration changes require Maker-Checker approval ({$dualControlCount} update request(s) submitted to Checker queue).";
        } elseif ($directUpdateCount > 0) {
            $message = 'Settings updated successfully.';
        } else {
            $message = 'No changes detected in system settings.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Roles tab — list all roles with their assigned permissions.
     */
    public function roles()
    {
        $roles = Role::with('permissions')->withCount('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        return view('admin.settings.roles', compact('roles', 'permissions'));
    }

    /**
     * Update permissions for a specific role.
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $before = $role->permissions->pluck('id')->toArray();
        $role->permissions()->sync($validated['permissions'] ?? []);
        $after = $role->fresh()->permissions->pluck('id')->toArray();

        AuditLog::createEntry(auth()->id(), 'update', 'Role', $role->id, ['permissions' => $before], ['permissions' => $after], $request->ip());

        return redirect()->route('admin.settings.roles')->with('success', "Permissions updated for role: {$role->display_name}.");
    }

    /**
     * Permissions tab — list all permissions grouped by module.
     */
    public function permissions()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $roles = Role::with('permissions')->orderBy('name')->get();
        return view('admin.settings.permissions', compact('permissions', 'roles'));
    }

    /**
     * Menus tab — manage sidebar menus.
     */
    public function menus()
    {
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        $permissions = Permission::orderBy('name')->get();
        return view('admin.settings.menus', compact('menus', 'permissions'));
    }

    /**
     * Update menu items.
     */
    public function updateMenus(Request $request)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.sort_order' => 'required|integer|min:0',
            'menus.*.is_active' => 'sometimes|boolean',
        ]);

        foreach ($validated['menus'] as $menuData) {
            Menu::where('id', $menuData['id'])->update([
                'sort_order' => $menuData['sort_order'],
                'is_active' => $menuData['is_active'] ?? false,
            ]);
        }

        return redirect()->route('admin.settings.menus')->with('success', 'Menu order updated.');
    }

    // ── Change Password ─────────────────────────────────────────

    /**
     * Show the change password form.
     */
    public function showChangePassword()
    {
        return view('admin.settings.change-password');
    }

    /**
     * Handle password change request.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = auth()->user();

        // Ensure new password isn't the same as the old one
        if (\Illuminate\Support\Facades\Hash::check($request->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'New password cannot be the same as your current password.']);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        AuditLog::createEntry(auth()->id(), 'update', 'User', $user->id, [], ['action' => 'password_changed'], $request->ip());

        return redirect()->route('admin.settings.change-password')->with('success', 'Password changed successfully.');
    }

    // ── Role Management ─────────────────────────────────────────

    /**
     * Create a new role.
     */
    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        // Auto-generate slug from display name
        $slug = \Illuminate\Support\Str::slug($validated['display_name'], '_');

        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->orWhere('name', $slug)->exists()) {
            $slug = $originalSlug . '_' . $counter++;
        }

        $role = Role::create([
            'name' => $slug,
            'slug' => $slug,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        AuditLog::createEntry(auth()->id(), 'create', 'Role', $role->id, [], $role->toArray(), $request->ip());

        return redirect()->route('admin.settings.roles')->with('success', "Role \"{$role->display_name}\" created successfully.");
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255|unique:roles,display_name,' . $role->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $before = $role->toArray();
        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
        ]);

        AuditLog::createEntry(auth()->id(), 'update', 'Role', $role->id, $before, $role->toArray(), $request->ip());

        return redirect()->route('admin.settings.roles')->with('success', "Role \"{$role->display_name}\" updated successfully.");
    }

    /**
     * Delete a non-system role.
     */
    public function deleteRole(Request $request, Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        // Check if any users are assigned to this role
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return back()->with('error', "Cannot delete role \"{$role->display_name}\": {$usersCount} user(s) are assigned to it.");
        }

        $roleName = $role->display_name;
        AuditLog::createEntry(auth()->id(), 'delete', 'Role', $role->id, $role->toArray(), [], $request->ip());
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.settings.roles')->with('success', "Role \"{$roleName}\" deleted.");
    }

    // ── Portal Change Password (JSON API) ───────────────────────

    /**
     * Handle password change for portal users (JSON response).
     */
    public function portalChangePassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $user = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Current password is incorrect.'], 422);
        }

        if (\Illuminate\Support\Facades\Hash::check($request->new_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'New password cannot be the same as your current password.'], 422);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        AuditLog::createEntry(auth()->id(), 'update', 'User', $user->id, [], ['action' => 'password_changed_portal'], $request->ip());

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    }
}
