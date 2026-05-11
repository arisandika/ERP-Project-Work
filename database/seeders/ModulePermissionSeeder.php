<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleModules = config('module-role-permissions', []);

        $allModulePermissions = collect(config('erp-modules'))
            ->map(fn($m) => $m['permission'])
            ->values();

        foreach ($roleModules as $roleName => $modules) {
            $role = Role::whereName($roleName)->first();

            if (!$role) {
                $this->command->warn("Role [{$roleName}] tidak ditemukan, dilewati.");
                continue;
            }

            // Pertahankan CRUD yang sudah ada, jangan disentuh
            $existingNonModulePermissions = $role->permissions
                ->whereNotIn('name', $allModulePermissions)
                ->pluck('name');

            // Permission modul sesuai config
            $modulePermissions = collect($modules)
                ->map(fn($key) => "module.access.{$key}")
                ->filter(fn($name) => Permission::whereName($name)->exists())
                ->values();

            $role->syncPermissions(
                $existingNonModulePermissions->merge($modulePermissions)->toArray()
            );

            $this->command->info("✓ Role [{$roleName}] → " . implode(', ', $modules));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('Module permissions seeding selesai.');
    }
}