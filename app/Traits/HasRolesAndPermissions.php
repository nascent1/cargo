<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;

trait HasRolesAndPermissions
{
    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has all of the given roles.
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->count() === count($roleSlugs);
    }

    /**
     * Check if user has a specific permission.
     *
     * Resolution order per SRS §3.3:
     * 1. super_admin role → bypass (always grant)
     * 2. user_permissions (direct overrides, temporal, granted/denied)
     * 3. role_permissions (inherited from roles)
     * 4. Default → DENY
     */
    public function hasPermission(string $permissionName): bool
    {
        // 1. super_admin role → bypass (always grant) per SRS §3.3
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // 2. Check direct user permissions (with temporal bounds)
        $directPermission = $this->permissions()
            ->where('permissions.name', $permissionName)
            ->where(function ($q) {
                $q->whereNull('user_permissions.start_date')
                  ->orWhere('user_permissions.start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('user_permissions.end_date')
                  ->orWhere('user_permissions.end_date', '>=', now());
            })
            ->first();

        if ($directPermission) {
            // granted = false means explicit DENY (overrides role grants)
            return (bool) $directPermission->pivot->granted;
        }

        // 3. Check role-based permissions
        $rolePermission = Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->where('permissions.name', $permissionName)
            ->exists();

        return $rolePermission;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $name) {
            if ($this->hasPermission($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all effective permissions for this user.
     */
    public function getAllPermissions(): Collection
    {
        // Direct user permissions (active, within date range)
        $directPermissions = $this->permissions()
            ->wherePivot('granted', true)
            ->where(function ($q) {
                $q->whereNull('user_permissions.start_date')
                  ->orWhere('user_permissions.start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('user_permissions.end_date')
                  ->orWhere('user_permissions.end_date', '>=', now());
            })
            ->get();

        // Role-based permissions
        $rolePermissions = Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->select('permissions.*')
            ->get();

        return $directPermissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role->id, ['assigned_by' => auth()->id()]);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(array $roles): void
    {
        if (empty($roles)) {
            $this->roles()->sync([]);
            return;
        }

        if (is_numeric($roles[0])) {
            $this->roles()->sync($roles);
        } else {
            $roleIds = Role::whereIn('slug', $roles)->pluck('id');
            $this->roles()->sync($roleIds);
        }
    }

    /**
     * Grant a permission directly to the user.
     */
    public function grantPermission(string $permissionName, ?string $startDate = null, ?string $endDate = null): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();
        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'granted' => true,
                'start_date' => $startDate ?? now(),
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Deny a permission directly (overrides role grants).
     */
    public function denyPermission(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();
        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'granted' => false,
                'start_date' => now(),
                'end_date' => null,
            ],
        ]);
    }

    /**
     * Revoke a direct permission from the user.
     */
    public function revokePermission(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }
}
