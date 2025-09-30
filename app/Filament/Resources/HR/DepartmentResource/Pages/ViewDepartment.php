<?php

namespace App\Filament\Resources\HR\DepartmentResource\Pages;

use App\Filament\Resources\HR\DepartmentResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewDepartment extends ViewRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->url(static::getResource()::getUrl()) 
                ->button()
                ->color('gray'),
        ];
    }

    // public function getTitle(): string
    // {
    //     return 'Detail Department ' . $this->record->name;
    // }
}
