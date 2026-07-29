<?php
namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Concerns\SyncsModulePermissions;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreateRole extends \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\CreateRole
{
    use SyncsModulePermissions;
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = Str::snake(trim($data['name']));

        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, [
                    'name',
                    'guard_name',
                    'select_all',
                    Utils::getTenantModelForeignKey(),
                ]);
            })
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, [
                'name',
                'guard_name',
                Utils::getTenantModelForeignKey(),
            ]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterCreate(): void
    {
        parent::afterCreate(); // biarkan Shield tetap sync permission resource/page/widget seperti biasa

        $this->syncModulePermissions();
    }
}
