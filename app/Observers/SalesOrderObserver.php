<?php

namespace App\Observers;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderItem;
use Illuminate\Support\Facades\DB;

class SalesOrderObserver
{
    public function saved(SalesOrder $so): void
    {
        // Trigger saat: baru dibuat already confirmed, atau status berubah ke confirmed
        $justConfirmed =
            ($so->wasRecentlyCreated && $so->status === 'confirmed')
            || ($so->wasChanged('status') && $so->status === 'confirmed');

        if (! $justConfirmed) {
            return;
        }

        // Idempotency: jangan bikin DO dobel (kecuali kamu memang mau auto-create setiap confirm)
        $hasDo = $so->deliveryOrders()
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasDo) {
            return;
        }

        DB::transaction(function () use ($so) {
            $so->loadMissing('items');

            $do = DeliveryOrder::create([
                'nx_sales_order_id' => $so->id,
                'nx_customer_id'    => $so->nx_customer_id,
                'nx_employee_id'    => $so->nx_employee_id ?? auth()->user()?->employee?->id,
                'do_date'           => now(),
                'status'            => 'ready', // atau 'draft' kalau mau diedit dulu
                'notes'             => $so->notes,
                // do_number auto terisi dari model event
            ]);

            foreach ($so->items as $item) {
                $qtyOrder = (int) ($item->qty ?? 0);

                DeliveryOrderItem::create([
                    'nx_delivery_order_id' => $do->id,
                    'item_type'            => $item->item_type,
                    'item_id'              => $item->item_id,
                    'item_code'            => (string) $item->item_code,
                    'item_name'            => (string) $item->item_name,

                    // karena ini DO pertama: quota = qty SO, kirim full by default
                    'qty_ordered'          => $qtyOrder,
                    'qty'                  => $qtyOrder,
                    'qty_remaining'        => 0,
                ]);
            }
        });
    }
}
