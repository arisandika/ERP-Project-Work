<?php

namespace App\Filament\Resources\Procurement\PurchaseReturnResource\Pages;

use App\Filament\Resources\Procurement\PurchaseReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
