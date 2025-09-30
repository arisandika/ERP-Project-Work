<?php

namespace App\Filament\Resources\HR\DepartmentResource\Pages;

use App\Filament\Resources\HR\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    // public function getTitle(): string
    // {
    //     return 'Edit Department ' . $this->record->name;
    // }
}
