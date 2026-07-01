<?php

namespace App\Filament\Resources\Finance\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\Finance\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;
}
