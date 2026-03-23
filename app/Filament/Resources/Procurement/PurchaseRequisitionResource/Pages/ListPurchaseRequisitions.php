<?php

namespace App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseRequisitions extends ListRecords
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
