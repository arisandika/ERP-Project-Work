<?php

namespace App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;

use App\Filament\Resources\Procurement\GoodsReceiptResource;
use App\Models\Procurement\GoodsReceipt;
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

            /** @var GoodsReceipt $record */
            $record = static::getModel()::create($data);

            $hasValidItem = false;

            foreach ($items as $item) {
                $qtyReceived = (int) ($item['quantity_received'] ?? 0);

                if ($qtyReceived <= 0) {
                    continue;
                }

                $purchaseOrderItemId = $item['purchase_order_item_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if (blank($purchaseOrderItemId) || blank($productId)) {
                    continue;
                }

                $hasValidItem = true;

                $record->items()->create([
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'product_id' => $productId,
                    'quantity_received' => $qtyReceived,
                    'scanned_sns' => $item['scanned_sns'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            if (! $hasValidItem) {
                throw new \Exception('Minimal harus ada satu item dengan qty diterima lebih dari 0. Pastikan PO sudah dipilih dan item sudah termuat.');
            }

            $record->load('items');

            $service = app(GoodsReceiptService::class);
            $service->processAfterCreation($record);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
