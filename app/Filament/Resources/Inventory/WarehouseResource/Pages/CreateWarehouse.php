<?php

namespace App\Filament\Resources\Inventory\WarehouseResource\Pages;

use App\Filament\Resources\Inventory\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    public function getTitle(): string
    {
        return 'Tambah Gudang';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
