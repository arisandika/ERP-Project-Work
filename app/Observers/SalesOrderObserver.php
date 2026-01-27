<?php

namespace App\Observers;

use App\Models\Sales\SalesOrder;

class SalesOrderObserver
{
    /**
     * Handle the SalesOrder "created" event.
     */
    public function created(SalesOrder $salesOrder): void
    {
        if ($salesOrder->promo_code_id) {
            $promo = $salesOrder->promoCode;

            if ($promo) {
                $promo->increment('times_used');

            }
        }
    }

    /**
     * Handle the SalesOrder "updated" event.
     */
    public function updated(SalesOrder $salesOrder): void
    {
        if ($salesOrder->isDirty('status') && $salesOrder->status === 'confirmed') {
            foreach ($salesOrder->items as $item) {
                if ($item->item_type === 'product' && $item->product) {
                    $item->product->decrement('stock_qty', $item->qty);
                }
            }
        }

        if ($salesOrder->isDirty('status') && $salesOrder->status === 'cancelled') {
            if ($salesOrder->getOriginal('status') === 'confirmed') {
                foreach ($salesOrder->items as $item) {
                    if ($item->item_type === 'product' && $item->product) {
                        $item->product->increment('stock_qty', $item->qty);
                    }
                }
            }
        }
    }

    /**
     * Handle the SalesOrder "deleted" event.
     */
    public function deleted(SalesOrder $salesOrder): void
    {
        if ($salesOrder->promo_code_id) {
            $salesOrder->promoCode->decrement('times_used');
        }
    }

    /**
     * Handle the SalesOrder "restored" event.
     */
    public function restored(SalesOrder $salesOrder): void
    {
        //
    }

    /**
     * Handle the SalesOrder "force deleted" event.
     */
    public function forceDeleted(SalesOrder $salesOrder): void
    {
        //
    }
}
