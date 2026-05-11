<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleObserver
{
    public function saved(Role $role): void
    {
        $modulesForRole = config("module-role-permissions.{$role->name}", []);

        if (empty($modulesForRole)) {
            return;
        }

        $modulePermissions = collect($modulesForRole)
            ->map(fn($key) => "module.access.{$key}")
            ->filter(fn($name) => Permission::whereName($name)->exists())
            ->toArray();

        // Kembalikan module permissions yang mungkin dihapus Shield
        $existing = $role->permissions->pluck('name')->toArray();
        $missing = array_diff($modulePermissions, $existing);

        if (!empty($missing)) {
            $role->givePermissionTo($missing);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}