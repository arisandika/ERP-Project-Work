<?php

namespace App\Services\Sales;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderItem;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Marketing\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    /**
     * 1. SECURITY: Kalkulasi di Backend
     */
    public function recalculateFormData(array $data): array
    {
        $subtotal = 0;

        $items = $data['items'] ?? [];

        foreach ($items as &$item) {
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

        $data['items'] = $items;
        $data['subtotal'] = $subtotal;

        $totalDiscount = 0;
        if (!empty($data['promo_code_id'])) {
            $promo = PromoCode::find($data['promo_code_id']);
            if ($promo) {
                $totalDiscount = $promo->type === 'percentage'
                    ? $subtotal * ((float) $promo->value / 100)
                    : (float) $promo->value;
            }
        }

        $data['discount_amount'] = min($totalDiscount, $subtotal);

        $taxPercent = (float) ($data['tax'] ?? 0);
        $data['tax'] = $taxPercent;

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
            $data = $this->recalculateFormData($data);

            // Validasi stok kalau status langsung confirmed
            if (($data['status'] ?? 'draft') === 'confirmed') {
                $this->validateStock($data['items'] ?? []);
            }

            $items = $data['items'] ?? [];
            $orderData = Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            $order = SalesOrder::create($orderData);

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
            $data = $this->recalculateFormData($data);

            // Validasi stok kalau status berubah ke confirmed
            if (($data['status'] ?? 'draft') === 'confirmed' && $order->status !== 'confirmed') {
                $this->validateStock($data['items'] ?? []);
            }

            $items = $data['items'] ?? [];
            $orderData = Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            $order->update($orderData);

            $order->items()->delete();
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order;
        });
    }

    /**
     * Validasi stok tersedia untuk semua item product
     */
    private function validateStock(array $items): void
    {
        foreach ($items as $item) {
            if (($item['item_type'] ?? 'product') !== 'product') {
                continue;
            }

            $avail = (float) ProductStock::where('product_id', $item['item_id'])
                ->sum('qty_available');
            $qty = (float) ($item['qty'] ?? 0);

            if ($avail < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok '{$item['item_name']}' tidak mencukupi! Tersedia: {$avail}, diminta: {$qty}",
                ]);
            }
        }
    }

    /**
     * 2. LOGIC CONFIRM ORDER (Booking Stok & Auto DO)
     */
    public function processConfirmation(SalesOrder $record, int $userId): void
    {
        DB::transaction(function () use ($record, $userId) {
            $record = SalesOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($record->id);

            StockTransaction::$autoUpdateStock = false;

            try {
                $alreadyReserved = StockTransaction::query()
                    ->where('reference_type', SalesOrder::class)
                    ->where('reference_id', $record->id)
                    ->where('mutation_type', 'reserve')
                    ->exists();

                if (! $alreadyReserved) {
                    foreach ($record->items as $item) {
                        if ($item->item_type !== 'product') {
                            continue;
                        }

                        $remaining = (float) $item->qty;
                        $stocks = ProductStock::where('product_id', $item->item_id)
                            ->where('qty_available', '>', 0)
                            ->orderBy('warehouse_id')
                            ->lockForUpdate()
                            ->get();

                        foreach ($stocks as $stockUtama) {
                            if ($remaining <= 0) {
                                break;
                            }

                            $qty = min((float) $stockUtama->qty_available, $remaining);
                            $stockAvailableBefore = (float) $stockUtama->qty_available;

                            $stockUtama->decrement('qty_available', $qty);
                            $stockUtama->increment('qty_reserved', $qty);

                            StockTransaction::create([
                                'transaction_code'  => $this->generateTransactionCode(),
                                'transaction_date'  => now(),
                                'product_id'        => $item->item_id,
                                'warehouse_id'      => $stockUtama->warehouse_id,
                                'mutation_type'     => 'reserve',
                                'type'              => 'keluar',
                                'quantity'          => $qty,
                                'stock_before'      => $stockAvailableBefore,
                                'stock_after'       => $stockAvailableBefore - $qty,
                                'price'             => 0,
                                'total_price'       => 0,
                                'reference_id'      => $record->id,
                                'reference_type'    => SalesOrder::class,
                                'reference_number'  => $record->order_number,
                                'notes'              => 'Booking Stok (SO Confirmed)',
                                'created_by'        => $userId,
                            ]);

                            $remaining -= $qty;
                        }

                        if ($remaining > 0) {
                            throw new \Exception("Stok Siap Jual untuk '{$item->item_name}' tidak mencukupi!");
                        }
                    }
                }

                // B. UPDATE PENGGUNAAN PROMO
                if (! $alreadyReserved && $record->promo_code_id) {
                    PromoCode::find($record->promo_code_id)?->increment('times_used');
                }

                // C. AUTO CREATE / FIX SURAT JALAN (DO) DRAFT
                $do = DeliveryOrder::where('nx_sales_order_id', $record->id)
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if (!$do) {
                    $do = DeliveryOrder::create([
                        'nx_sales_order_id' => $record->id,
                        'nx_customer_id'    => $record->nx_customer_id,
                        'nx_employee_id'    => $record->nx_employee_id,
                        'do_date'           => now(),
                        'status'            => 'draft',
                    ]);
                }

                if ($do->items()->doesntExist()) {
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

                if (blank($do->do_number)) {
                    $do->update([
                        'do_number' => DeliveryOrder::generateDoNumber(),
                    ]);
                }
            } finally {
                StockTransaction::$autoUpdateStock = true;
            }
        });
    }

    private function generateTransactionCode(): string
    {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/ST-RES/NEX/{$roman}/" . now()->year;
        $last = StockTransaction::where('transaction_code', 'like', $prefix)->orderByDesc('id')->value('transaction_code');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-RES/NEX/{$roman}/" . now()->year;
    }
}
