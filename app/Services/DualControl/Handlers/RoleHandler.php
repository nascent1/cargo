<?php

namespace App\Services\DualControl\Handlers;

use App\Models\AuditLog;
use App\Models\DualControlRequest;
use App\Models\Role;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Roles and the permissions they grant.
 */
class RoleHandler implements DualControlHandler
{
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $after = $request->payload_after ?? [];

        if ($request->operation_type === 'role_create') {
            $slug = $base = Str::slug($after['display_name'], '_');
            for ($i = 1; Role::where('slug', $slug)->orWhere('name', $slug)->exists(); $i++) {
                $slug = "{$base}_{$i}";
            }
            $role = Role::create(['name' => $slug, 'slug' => $slug, 'display_name' => $after['display_name'], 'description' => $after['description'] ?? null, 'is_system' => false]);
            $request->update(['resource_id' => $role->id, 'subject_key' => "role:{$role->id}"]);
            AuditLog::createEntry($request->maker_id, 'create', 'Role', $role->id, [], $role->toArray(), null);

            return;
        }

        $role = Role::find($request->resource_id) ?? throw new DualControlException('This role no longer exists.');

        switch ($request->operation_type) {
            case 'role_update':
                StaleChangeGuard::assertFresh($request->payload_before, $after, $role->only(['display_name', 'description']));
                $role->update(['display_name' => $after['display_name'], 'description' => $after['description'] ?? null]);
                break;

            case 'role_permissions_update':
                $current = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                StaleChangeGuard::assertFresh($request->payload_before, $after, ['permissions' => $current]);
                $role->permissions()->sync($after['permissions'] ?? []);
                AuditLog::createEntry($request->maker_id, 'update', 'Role', $role->id, ['permissions' => $current], ['permissions' => $after['permissions'] ?? []], null);
                break;

            case 'role_delete':
                if ($role->is_system) {
                    throw new DualControlException('System roles cannot be deleted.');
                }
                if ($count = $role->users()->count()) {
                    throw new DualControlException("{$count} user(s) now have this role, so it can't be deleted.");
                }
                AuditLog::createEntry($request->maker_id, 'delete', 'Role', $role->id, $role->toArray(), [], null);
                $role->permissions()->detach();
                $role->delete();
                break;
        }
    }
}
