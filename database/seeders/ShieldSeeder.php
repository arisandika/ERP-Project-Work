<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Role dasar yang wajib ada (super_admin selalu full access via Gate::before)
        $roleModel = Utils::getRoleModel();
        $roleModel::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Generate permission module.access.* SECARA DINAMIS dari config/erp-modules.php
        // Kalau kamu nambah modul baru di config itu, cukup jalankan seeder ini sekali,
        // habis itu tinggal centang di form Role — TIDAK PERLU seeder mapping lagi.
        $permissionModel = Utils::getPermissionModel();

        collect(config('erp-modules', []))
            ->keys()
            ->each(function (string $moduleKey) use ($permissionModel) {
                $permissionModel::firstOrCreate([
                    'name' => "module.access.{$moduleKey}",
                    'guard_name' => 'web',
                ]);
            });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Shield Seeding Completed (module permissions synced from config/erp-modules.php).');
    }
}