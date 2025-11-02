<?php

namespace App\Filament\Resources\Inventory\ProductStockResource\Pages;

use App\Filament\Resources\Inventory\ProductStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductStocks extends ListRecords
{
    protected static string $resource = ProductStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

