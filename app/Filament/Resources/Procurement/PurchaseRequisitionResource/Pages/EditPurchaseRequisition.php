<?php

namespace App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequisition extends EditRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
