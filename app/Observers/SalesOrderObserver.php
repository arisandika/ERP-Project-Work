<?php

namespace App\Observers;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\PromoCode;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalesOrderObserver
{
    public function created(SalesOrder $salesOrder): void
    {
        //
    }

    public function updated(SalesOrder $salesOrder): void
    {
        // Cek apakah status berubah
        if ($salesOrder->isDirty('status')) {
            $newStatus = $salesOrder->status;
            $oldStatus = $salesOrder->getOriginal('status');

            // --------------------------------------------------------
            // 1. KASUS: DEAL (Draft -> Confirmed)
            // --------------------------------------------------------
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {

                DB::transaction(function () use ($salesOrder) {
                    // A. POTONG STOK
                    foreach ($salesOrder->items as $item) {
                        if ($item->item_type === 'product' && $item->product) {
                            $item->product->decrement('stock_qty', $item->qty);
                        }
                    }

                    // B. POTONG KUOTA PROMO
                    if ($salesOrder->promo_code_id) {
                        $promo = PromoCode::find($salesOrder->promo_code_id);
                        if ($promo) {
                            $promo->increment('times_used');
                        }
                    }
                });

                // (Opsional) Notif ke user
                Notification::make()->title('Stok & Promo Berhasil di-Booking')->success()->send();
            }

            // --------------------------------------------------------
            // 2. KASUS: BATAL (Confirmed -> Cancelled)
            // --------------------------------------------------------
            if ($newStatus === 'cancelled' && $oldStatus === 'confirmed') {

                DB::transaction(function () use ($salesOrder) {
                    // A. BALIKIN STOK
                    foreach ($salesOrder->items as $item) {
                        if ($item->item_type === 'product' && $item->product) {
                            $item->product->increment('stock_qty', $item->qty);
                        }
                    }

                    // B. BALIKIN KUOTA PROMO
                    if ($salesOrder->promo_code_id) {
                        $promo = PromoCode::find($salesOrder->promo_code_id);
                        if ($promo) {
                            $promo->decrement('times_used');
                        }
                    }
                });
            }
        }
    }

    // --------------------------------------------------------
    // 3. KASUS: DIHAPUS (Hapus Permanen)
    // --------------------------------------------------------
    public function deleted(SalesOrder $salesOrder): void
    {

        if ($salesOrder->status === 'confirmed') {
            DB::transaction(function () use ($salesOrder) {

                // A. Balikin Promo
                if ($salesOrder->promo_code_id) {
                    $promo = PromoCode::find($salesOrder->promo_code_id);
                    if ($promo) {
                        $promo->decrement('times_used');
                    }
                }

                // B. Balikin Stok
                foreach ($salesOrder->items as $item) {
                    if ($item->item_type === 'product' && $item->product) {
                        $item->product->increment('stock_qty', $item->qty);
                    }
                }
            });
        }
    }
}
