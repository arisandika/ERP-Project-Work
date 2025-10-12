<?php

namespace App\Filament\Resources\HR\LeaveResource\Pages;

use App\Filament\Resources\HR\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Cuti'),
        ];
    }
}
