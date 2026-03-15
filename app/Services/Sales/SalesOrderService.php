<?php

namespace App\Services\Sales;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderItem;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Marketing\PromoCode;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesOrderService
{
    /**
     * 1. SECURITY: Kalkulasi di Backend
     */
    public function recalculateFormData(array $data): array
    {
        $subtotal = 0;

        // Ensure items array exists
        $items = $data['items'] ?? [];

        foreach ($items as &$item) {
            // Ensure item data is populated from Product/Service/Package if missing (Backend Fail-safe)
            if (!empty($item['item_id'])) {
                $type = $item['item_type'] ?? 'product';
                $model = match ($type) {
                    'product' => \App\Models\Inventory\Product::find($item['item_id']),
                    'service' => \App\Models\Inventory\Service::find($item['item_id']),
                    'package' => \App\Models\Inventory\Package::find($item['item_id']),
                    default => null
                };

                if ($model) {
                    $item['item_code'] = $item['item_code'] ?? ($model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code);
                    $item['item_name'] = $item['item_name'] ?? ($model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name);
                }
            }

            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['line_total'] = $qty * $price;
            $subtotal += $item['line_total'];
        }
        $data['items'] = $items; // Save back modified items

        $data['subtotal'] = $subtotal;

        $totalDiscount = 0;
        if (!empty($data['promo_code_id'])) {
            $promo = PromoCode::find($data['promo_code_id']);
            if ($promo) {
                $totalDiscount = $promo->type === 'percentage' ? $subtotal * ((float)$promo->value / 100) : (float)$promo->value;
            }
        }

        $data['discount_amount'] = min($totalDiscount, $subtotal);
        $taxPercent = (float) ($data['tax'] ?? 0);
        $afterDiscount = $subtotal - $data['discount_amount'];
        $data['grand_total'] = $afterDiscount + ($afterDiscount * ($taxPercent / 100));

        return $data;
    }

    /**
     * Create Order with Manual Item Handling
     */
    public function createOrder(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            // 1. Recalculate
            $data = $this->recalculateFormData($data);

            // 2. Extract Items
            $items = $data['items'] ?? [];
            $orderData = Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            // 3. Create Header
            $order = SalesOrder::create($orderData);

            // 4. Create Items
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order;
        });
    }

    /**
     * Update Order with Manual Item Handling
     */
    public function updateOrder(SalesOrder $order, array $data): SalesOrder
    {
        return DB::transaction(function () use ($order, $data) {
            // 1. Recalculate
            $data = $this->recalculateFormData($data);

            // 2. Extract Items
            $items = $data['items'] ?? [];
            $orderData = Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            // 3. Update Header
            $order->update($orderData);

            // 4. Sync Items (Delete all and recreate for simplicity, or implement smart sync)
            $order->items()->delete();
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order;
        });
    }

    /**
     * 2. LOGIC CONFIRM ORDER (Booking Stok & Auto DO)
     */
    public function processConfirmation(SalesOrder $record, int $userId): void
    {
        DB::transaction(function () use ($record, $userId) {
            $warehouseUtamaId = Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;
            StockTransaction::$autoUpdateStock = false;

            // A. BOOKING STOK
            foreach ($record->items as $item) {
                if ($item->item_type === 'product') {
                    $stockUtama = ProductStock::where('product_id', $item->item_id)
                        ->where('warehouse_id', $warehouseUtamaId)
                        ->lockForUpdate()
                        ->first();

                    if (!$stockUtama || $stockUtama->qty_available < $item->qty) {
                        throw new \Exception("Stok Siap Jual untuk '{$item->item_name}' tidak mencukupi!");
                    }

                    $stockAvailableBefore = $stockUtama->qty_available;
                    $stockUtama->decrement('qty_available', $item->qty);
                    $stockUtama->increment('qty_reserved', $item->qty); // Pindah ke status Booking

                    StockTransaction::create([
                        'transaction_code' => $this->generateTransactionCode(),
                        'transaction_date' => now(),
                        'product_id'       => $item->item_id,
                        'warehouse_id'     => $warehouseUtamaId,
                        'mutation_type'    => 'reserve',
                        'type'             => 'keluar',
                        'quantity'         => $item->qty,
                        'stock_before'     => $stockAvailableBefore,
                        'stock_after'      => $stockAvailableBefore - $item->qty,
                        'reference_id'     => $record->id,
                        'reference_type'   => SalesOrder::class,
                        'no_reference'     => $record->order_number,
                        'notes'            => 'Booking Stok (SO Confirmed)',
                        'created_by'       => $userId,
                    ]);
                }
            }
            StockTransaction::$autoUpdateStock = true;

            // B. UPDATE PENGGUNAAN PROMO
            if ($record->promo_code_id) {
                PromoCode::find($record->promo_code_id)?->increment('times_used');
            }

            // C. AUTO CREATE SURAT JALAN (DO) DRAFT
            $exists = DeliveryOrder::where('nx_sales_order_id', $record->id)->where('status', '!=', 'cancelled')->exists();
            if (!$exists) {
                $do = DeliveryOrder::create([
                    'nx_sales_order_id' => $record->id,
                    'nx_customer_id'    => $record->nx_customer_id,
                    'nx_employee_id'    => $record->nx_employee_id,
                    'do_number'         => $this->generateDeliveryNumber(),
                    'do_date'           => now(),
                    'status'            => 'draft',
                ]);

                foreach ($record->items as $item) {
                    DeliveryOrderItem::create([
                        'nx_delivery_order_id' => $do->id,
                        'item_type'            => $item->item_type,
                        'item_id'              => $item->item_id,
                        'item_code'            => $item->item_code,
                        'item_name'            => $item->item_name,
                        'qty_ordered'          => $item->qty,
                        'qty'                  => $item->qty,
                        'qty_remaining'        => 0,
                    ]);
                }
            }
        });
    }

    private function generateTransactionCode(): string {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/ST-RES/NEX/{$roman}/" . now()->year;
        $last = StockTransaction::where('transaction_code', 'like', $prefix)->orderByDesc('id')->value('transaction_code');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-RES/NEX/{$roman}/" . now()->year;
    }

    private function generateDeliveryNumber(): string {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/DO/NEX/{$roman}/" . now()->year;
        $last = DeliveryOrder::withTrashed()->where('do_number', 'like', $prefix)->orderByDesc('id')->value('do_number');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/DO/NEX/{$roman}/" . now()->year;
    }
}
