<?php

namespace App\Filament\Resources\Inventory\SerialNumberResource\Pages;

use App\Filament\Resources\Inventory\SerialNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSerialNumbers extends ListRecords
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
