<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    use SubmitsDualControl;

    /** Settings with a typed input on the General tab, checked before they go to approval. */
    private const TYPED_RULES = [
        'close_of_business_buffer_minutes' => 'integer|min:0|max:240',
        'close_of_business_fallback_time' => 'date_format:H:i',
        'default_shift_hours' => 'numeric|min:0.5|max:24',
        'shift_rest_hours' => 'numeric|min:0|max:24',
        'shift_warning_minutes' => 'integer|min:0|max:120',
        'exchange_rate_update_frequency' => 'integer|min:0|max:365',
    ];

    /** Shown on the General tab but set from the rate prompt / Exchange Rates (dated, through dual control). */
    private const SET_ELSEWHERE = ['usd_to_tzs_exchange_rate', 'exchange_rate_last_updated'];

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
        $typed = collect(self::TYPED_RULES);
        $request->validate(
            ['settings' => 'required|array'] + $typed->mapWithKeys(fn ($rule, $key) => ["settings.{$key}" => "sometimes|required|{$rule}"])->all(),
            [],
            $typed->mapWithKeys(fn ($rule, $key) => ["settings.{$key}" => str_replace('_', ' ', $key)])->all(),
        );
        // Not validated(): with rules for some keys it would drop every other setting
        $validated = ['settings' => (array) $request->input('settings', [])];
        $dualControlCount = 0;
        $directUpdateCount = 0;
        $scheduledCount = 0;
        $blocked = [];

        foreach ($validated['settings'] as $key => $value) {
            if (in_array($key, self::SET_ELSEWHERE, true)) {
                continue;
            }
            $config = SystemConfig::where('key_name', $key)->first();

            if ($config && $config->value !== $value) {
                $operationType = ($key === 'commission_default_method') ? 'commission_method_update' : 'setting_update';
                try {
                    $dcReq = $dualControl->submit($operationType, 'system_config', $config->id, [
                        'before' => ['value' => $config->value, 'key_name' => $key],
                        'after'  => ['value' => $value, 'key_name' => $key],
                    ], ['commence_at' => $this->takesEffect($request, $operationType)]);
                } catch (\App\Services\DualControl\DualControlException $e) {
                    $blocked[] = $key;
                    continue;
                }

                match ($dcReq->status) {
                    'pending' => $dualControlCount++,
                    'scheduled' => $scheduledCount++,
                    default => $directUpdateCount++,
                };
            }
        }

        $parts = array_filter([
            $directUpdateCount ? "{$directUpdateCount} updated" : null,
            $scheduledCount ? "{$scheduledCount} scheduled" : null,
            $dualControlCount ? "{$dualControlCount} awaiting approval" : null,
        ]);
        $message = $parts ? 'Settings: ' . implode(', ', $parts) . '.' : 'No changes detected in system settings.';
        if ($blocked) {
            return redirect()->back()->with('success', $parts ? $message : null)
                ->with('error', 'Not changed (another change to it is open): ' . implode(', ', $blocked) . '.');
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

        // Through dual control (role_permissions_update)
        $before = $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $after = collect($validated['permissions'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return $this->submitChange($request, 'role_permissions_update', 'role', $role->id,
            ['before' => ['permissions' => $before], 'after' => ['permissions' => $after, 'role' => $role->display_name]],
            "Permissions updated for role: {$role->display_name}.", route('admin.settings.roles'));
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

        // Through dual control (role_create); the slug is chosen when the role is created (RoleHandler)
        return $this->submitChange($request, 'role_create', 'role', 0, ['after' => $validated],
            "Role \"{$validated['display_name']}\" created.", route('admin.settings.roles'));
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

        return $this->submitChange($request, 'role_update', 'role', $role->id, [
            'before' => $role->only(['display_name', 'description']),
            'after' => ['display_name' => $validated['display_name'], 'description' => $validated['description'] ?? null],
        ], "Role \"{$validated['display_name']}\" updated.", route('admin.settings.roles'));
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

        return $this->submitChange($request, 'role_delete', 'role', $role->id,
            ['before' => $role->only(['display_name', 'description', 'slug'])], "Role \"{$role->display_name}\" deleted.", route('admin.settings.roles'));
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
