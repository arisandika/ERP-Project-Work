<?php

namespace App\Filament\Resources\HR\ShiftResource\Pages;

use App\Filament\Resources\HR\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Shift'),
        ];
    }
}
