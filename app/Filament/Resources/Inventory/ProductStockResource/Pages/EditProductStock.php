<?php

namespace App\Filament\Resources\Inventory\ProductStockResource\Pages;

use App\Filament\Resources\Inventory\ProductStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductStock extends EditRecord
{
    protected static string $resource = ProductStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

