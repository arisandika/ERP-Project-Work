<?php

namespace App\Filament\Resources\AfterSales\SupplierWarrantyResource\Pages;

use App\Filament\Resources\AfterSales\SupplierWarrantyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierWarranty extends EditRecord
{
    protected static string $resource = SupplierWarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
