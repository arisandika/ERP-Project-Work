<?php

namespace App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;

use App\Filament\Resources\Procurement\GoodsReceiptResource;
use App\Services\Procurement\GoodsReceiptService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            /** @var \App\Models\Procurement\GoodsReceipt $record */
            $record = static::getModel()::create($data);

            foreach ($items as $item) {
                $qtyReceived = (int) ($item['quantity_received'] ?? 0);

                if ($qtyReceived <= 0) {
                    continue;
                }

                $record->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity_received' => $qtyReceived,
                    'scanned_sns' => $item['scanned_sns'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $record->load('items');

            app(GoodsReceiptService::class)->processAfterCreation($record);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
