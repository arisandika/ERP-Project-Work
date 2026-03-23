<?php

namespace App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;

use App\Filament\Resources\Procurement\GoodsReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
