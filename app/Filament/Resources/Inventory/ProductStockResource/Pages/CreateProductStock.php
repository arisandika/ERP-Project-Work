<?php

namespace App\Filament\Resources\Inventory\ProductStockResource\Pages;

use App\Filament\Resources\Inventory\ProductStockResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductStock extends CreateRecord
{
    protected static string $resource = ProductStockResource::class;
}

