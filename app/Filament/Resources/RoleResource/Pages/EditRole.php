<?php
namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Concerns\SyncsModulePermissions;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EditRole extends \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\EditRole
{
    use SyncsModulePermissions;
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        $this->syncModulePermissions();
    }
}
