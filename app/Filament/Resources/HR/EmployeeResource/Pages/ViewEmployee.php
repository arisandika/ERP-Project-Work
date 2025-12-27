<?php

namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\HasRelationManagers;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    use HasRelationManagers;
    
    protected static string $resource = EmployeeResource::class;

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

    public function getTitle(): string
    {
        return 'Lihat Karyawan';
    }
}
