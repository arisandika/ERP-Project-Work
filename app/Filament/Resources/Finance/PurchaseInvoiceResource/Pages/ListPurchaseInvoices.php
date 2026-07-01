<?php

namespace App\Filament\Resources\Finance\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\Finance\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
