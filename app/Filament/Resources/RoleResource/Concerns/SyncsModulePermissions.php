<?php
namespace App\Filament\Resources\RoleResource\Concerns;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait SyncsModulePermissions
{
    protected function syncModulePermissions(): void
    {
        $selected = collect($this->data['module_permissions'] ?? []);

        // Modul baru (belum pernah ada permission-nya di DB) otomatis dibuat di sini.
        // Ini yang bikin nambah modul baru di config/erp-modules.php TIDAK perlu seeding lagi.
        $selected->each(
            fn(string $name) => Permission::findOrCreate($name, 'web')
        );

        // Additive: hanya nambah, tidak menyentuh permission lain (resource/page/widget)
        // yang sudah di-sync oleh Shield sendiri di afterCreate/afterSave induk.
        $this->record->givePermissionTo($selected->all());

        // Cabut module permission yang di-uncheck, tanpa menyentuh permission non-modul.
        $allModuleKeys = collect(config('erp-modules', []))->pluck('permission');
        $toRevoke      = $allModuleKeys->diff($selected);
        $this->record->revokePermissionTo($toRevoke->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
