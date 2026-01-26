<?php

namespace App\Observers;

use App\Models\Sales\DeliveryOrder;
use App\Models\Inventory\Product; // Sesuaikan namespace Product kamu
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderObserver
{
    public function updated(DeliveryOrder $deliveryOrder): void
    {
        if ($deliveryOrder->isDirty('status')) {
            $newStatus = $deliveryOrder->status;
            $oldStatus = $deliveryOrder->getOriginal('status');

            // 1. Barang Keluar (Draft/Ready -> On Delivery)
            if ($newStatus === 'on_delivery' && $oldStatus !== 'on_delivery') {
                $this->processStockReduction($deliveryOrder);
            }

            // 2. Batal Kirim (On Delivery -> Cancelled)
            if ($newStatus === 'cancelled' && $oldStatus === 'on_delivery') {
                $this->processStockRestoration($deliveryOrder);
            }
        }
    }

    protected function processStockReduction(DeliveryOrder $deliveryOrder)
    {
        DB::transaction(function () use ($deliveryOrder) {
            foreach ($deliveryOrder->items as $item) {
                // Hanya proses jika tipe product
                if ($item->item_type === 'product' || is_null($item->item_type)) {
                    $product = Product::lockForUpdate()->find($item->item_id);

                    if ($product) {
                        if ($product->stock < $item->qty) {
                            throw ValidationException::withMessages([
                                'status' => "Stok {$product->name} Kurang! Sisa: {$product->stock}"
                            ]);
                        }
                        // Ganti 'stock' dengan nama kolom stok di DB kamu (misal: 'qty' / 'stock_qty')
                        $product->decrement('stock', $item->qty);
                    }
                }
            }
        });

        Notification::make()->title('Stok Berkurang')->success()->sendToDatabase(auth()->user());
    }

    protected function processStockRestoration(DeliveryOrder $deliveryOrder)
    {
        DB::transaction(function () use ($deliveryOrder) {
            foreach ($deliveryOrder->items as $item) {
                if ($item->item_type === 'product' || is_null($item->item_type)) {
                    $product = Product::lockForUpdate()->find($item->item_id);
                    if ($product) {
                        $product->increment('stock', $item->qty);
                    }
                }
            }
        });

        Notification::make()->title('Stok Dikembalikan')->warning()->sendToDatabase(auth()->user());
    }
}
