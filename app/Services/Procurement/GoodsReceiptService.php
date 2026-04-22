<?php

namespace App\Services\Procurement;

use App\Models\Finance\FinancialRecord;
use App\Models\Inventory\SerialNumber as ProductSerial;
use App\Models\Inventory\StockTransaction;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function processAfterCreation(GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $receipt->loadMissing([
                'items',
                'receiver.employee',
            ]);

            $po = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($receipt->purchase_order_id);

            $totalReceivedValue = 0;
            $employeeId = $receipt->receiver?->employee?->id;

            $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $monthRoman = $romanMonths[now()->month - 1];
            $year = now()->year;

            $lastTxCode = StockTransaction::query()
                ->where('transaction_code', 'like', "%/ST-IN/NEX/{$monthRoman}/{$year}")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('transaction_code');

            $sequence = $lastTxCode
                ? ((int) (explode('/', $lastTxCode)[0] ?? 0)) + 1
                : 1;

            foreach ($receipt->items as $grItem) {
                $qty = (int) ($grItem->quantity_received ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $poItem = PurchaseOrderItem::query()
                    ->findOrFail($grItem->purchase_order_item_id);

                $remainingQty = (int) $poItem->quantity - (int) $poItem->quantity_received;

                if ($qty > $remainingQty) {
                    throw new \Exception("Qty diterima untuk item {$poItem->id} melebihi sisa PO.");
                }

                $sns = [];

                if (! empty($grItem->scanned_sns)) {
                    $sns = array_values(array_unique(array_filter(
                        array_map('trim', preg_split('/\r\n|\r|\n/', $grItem->scanned_sns))
                    )));
                }

                if (! empty($sns) && count($sns) !== $qty) {
                    throw new \Exception("Jumlah serial number tidak sesuai dengan qty untuk product ID {$grItem->product_id}.");
                }

                foreach ($sns as $sn) {
                    $alreadyExists = ProductSerial::query()
                        ->where('serial_number', $sn)
                        ->exists();

                    if ($alreadyExists) {
                        throw new \Exception("Serial Number {$sn} sudah terdaftar.");
                    }
                }

                $poItem->increment('quantity_received', $qty);

                $unitPrice = round((float) ($poItem->unit_price ?? 0), 2);
                $lineTotal = round($qty * $unitPrice, 2);

                $totalReceivedValue += $lineTotal;

                $existingStockTx = StockTransaction::query()
                    ->where('reference_type', GoodsReceipt::class)
                    ->where('reference_id', $receipt->id)
                    ->where('product_id', $grItem->product_id)
                    ->where('mutation_type', 'stock_in')
                    ->exists();

                if (! $existingStockTx) {
                    $txCode = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/ST-IN/NEX/{$monthRoman}/{$year}";

                    StockTransaction::create([
                        'transaction_code' => $txCode,
                        'reference_number' => $receipt->gr_number,
                        'mutation_type' => 'stock_in',
                        'type' => 'masuk',
                        'product_id' => $grItem->product_id,
                        'warehouse_id' => $receipt->warehouse_id,
                        'transaction_date' => $receipt->receipt_date,
                        'quantity' => $qty,
                        'price' => $unitPrice,
                        'total_price' => $lineTotal,
                        'notes' => "Penerimaan GR {$receipt->gr_number} (PO {$po->po_number})",
                        'created_by' => $receipt->received_by,
                        'reference_type' => GoodsReceipt::class,
                        'reference_id' => $receipt->id,
                    ]);

                    $sequence++;
                }

                if (! empty($sns)) {
                    $serialData = [];

                    foreach ($sns as $sn) {
                        $serialData[] = [
                            'product_id' => $grItem->product_id,
                            'warehouse_id' => $receipt->warehouse_id,
                            'serial_number' => $sn,
                            'status' => ProductSerial::STATUS_AVAILABLE,
                            'supplier_id' => $receipt->supplier_id,
                            'purchase_order_id' => $po->id,
                            'customer_id' => null,
                            'inbound_date' => $receipt->receipt_date,
                            'outbound_date' => null,
                            'warranty_expired_at' => now()->addYear(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    ProductSerial::insert($serialData);
                }
            }

            if ($totalReceivedValue > 0) {
                $taxRate = ((float) ($po->subtotal ?? 0)) > 0
                    ? ((float) ($po->tax_amount ?? 0) / (float) $po->subtotal)
                    : 0;

                $newDebtAmount = round($totalReceivedValue * (1 + $taxRate), 2);

                $existingFinancialRecord = FinancialRecord::query()
                    ->where('reference_type', GoodsReceipt::class)
                    ->where('reference_id', $receipt->id)
                    ->where('type', 'hutang')
                    ->exists();

                if (! $existingFinancialRecord) {
                    FinancialRecord::create([
                        'transaction_date' => $receipt->receipt_date,
                        'type' => 'hutang',
                        'amount' => $newDebtAmount,
                        'category' => 'Accounts Payable',
                        'description' => "Hutang dagang dari GR {$receipt->gr_number} (PO {$po->po_number})",
                        'reference_number' => $receipt->gr_number,
                        'reference_type' => GoodsReceipt::class,
                        'reference_id' => $receipt->id,
                        'created_by' => $employeeId,
                    ]);
                }
            }

            $receipt->update([
                'status' => GoodsReceipt::STATUS_COMPLETED,
            ]);

            $po->refresh();

            $po->update([
                'status' => $this->determinePurchaseOrderStatus($po),
            ]);
        });
    }

    private function determinePurchaseOrderStatus(PurchaseOrder $po): string
    {
        $po->loadMissing('items');

        $hasReceived = false;
        $allCompleted = true;

        foreach ($po->items as $item) {
            $ordered = (int) ($item->quantity ?? 0);
            $received = (int) ($item->quantity_received ?? 0);

            if ($received > 0) {
                $hasReceived = true;
            }

            if ($received < $ordered) {
                $allCompleted = false;
            }
        }

        if ($allCompleted && $hasReceived) {
            return 'completed';
        }

        if ($hasReceived) {
            return 'partial';
        }

        return 'sent';
    }
}
