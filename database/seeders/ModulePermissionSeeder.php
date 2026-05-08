<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modulePermissions = [
            'access module attendance',
            'access module hr',
            'access module inventory',
            'access module procurement',
            'access module finance',
            'access module crm',
            'access module sales',
            'access module project',
            'access module marketing',
            'access module system',
        ];

        foreach ($modulePermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | Contoh mapping role
        |--------------------------------------------------------------------------
        | Sesuaikan nama role dengan role yang sudah ada di database.
        | Kalau nama role lu beda, tinggal ubah string-nya.
        */

        $this->givePermissionsToRole('staff', [
            'access module attendance',
        ]);

        $this->givePermissionsToRole('hr', [
            'access module attendance',
            'access module hr',
            'access module finance',
        ]);

        $this->givePermissionsToRole('finance', [
            'access module finance',
        ]);

        $this->givePermissionsToRole('sales', [
            'access module sales',
        ]);

        $this->givePermissionsToRole('procurement', [
            'access module procurement',
            'access module inventory',
        ]);

        $this->givePermissionsToRole('project_manager', [
            'access module project',
        ]);

        $this->givePermissionsToRole('marketing', [
            'access module marketing',
        ]);

        $this->givePermissionsToRole('admin', [
            'access module attendance',
            'access module hr',
            'access module inventory',
            'access module procurement',
            'access module finance',
            'access module crm',
            'access module sales',
            'access module project',
            'access module marketing',
        ]);

        $this->givePermissionsToRole('manager', [
            'access module attendance',
            'access module hr',
            'access module inventory',
            'access module procurement',
            'access module finance',
            'access module crm',
            'access module sales',
            'access module project',
            'access module marketing',
            'access module system',
        ]);

        $this->givePermissionsToRole('owner', $modulePermissions);
        $this->givePermissionsToRole('super_admin', $modulePermissions);
    }

    private function givePermissionsToRole(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        $role->givePermissionTo($permissions);
    }
}
