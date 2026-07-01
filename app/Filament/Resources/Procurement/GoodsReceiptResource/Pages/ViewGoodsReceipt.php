<?php

namespace App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;

use App\Filament\Resources\Procurement\GoodsReceiptResource;
use App\Models\Procurement\GoodsReceipt;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceipt extends ViewRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function resolveRecord(string|int $key): GoodsReceipt
    {
        return static::getResource()::getModel()::query()
            ->with([
                'items.product',
                'purchaseOrder',
                'supplier',
                'warehouse',
                'receiver',
            ])
            ->findOrFail($key);
    }
}
