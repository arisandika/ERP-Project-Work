<?php

namespace App\Filament\Resources\Inventory\WarehouseResource\Pages;

use App\Filament\Resources\Inventory\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Gudang'),
        ];
    }
}
