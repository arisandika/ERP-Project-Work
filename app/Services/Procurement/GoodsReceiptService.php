<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber as ProductSerial;
use App\Models\Finance\FinancialRecord;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function processAfterCreation(\App\Models\Procurement\GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {

            $po = PurchaseOrder::lockForUpdate()->findOrFail($receipt->purchase_order_id);

            $allCompleted = true;
            $totalReceivedValueToday = 0;

            // ===== generate transaction sequence =====
            $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $monthRoman = $romanMonths[now()->month - 1];
            $year = now()->year;

            $lastTx = StockTransaction::where('transaction_code', 'like', "%/ST-IN/NEX/{$monthRoman}/{$year}")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('transaction_code');

            $seq = $lastTx ? ((int) explode('/', $lastTx)[0]) + 1 : 1;

            foreach ($receipt->items as $grItem) {

                $qty = (int) $grItem->quantity_received;

                if ($qty <= 0) {
                    continue;
                }

                $poItem = PurchaseOrderItem::findOrFail($grItem->purchase_order_item_id);

                // ===== VALIDASI SN =====
                $sns = [];

                if (!empty($grItem->scanned_sns)) {
                    $sns = array_values(array_unique(array_filter(
                        array_map('trim', explode("\n", $grItem->scanned_sns))
                    )));
                }

                // 👉 VALIDASI: kalau pakai SN, jumlah harus sama
                if (!empty($sns) && count($sns) !== $qty) {
                    throw new \Exception("Jumlah SN tidak sesuai dengan qty untuk product ID {$grItem->product_id}");
                }

                // 👉 VALIDASI: SN tidak boleh duplicate
                foreach ($sns as $sn) {
                    if (ProductSerial::where('serial_number', $sn)->exists()) {
                        throw new \Exception("Serial Number {$sn} sudah terdaftar.");
                    }
                }

                // ===== UPDATE PO ITEM =====
                $poItem->increment('quantity_received', $qty);

                // ===== HITUNG NILAI =====
                $unitPrice = (float) $poItem->unit_price;
                $total = $qty * $unitPrice;

                $totalReceivedValueToday += $total;

                // ===== CREATE STOCK TRANSACTION =====
                $txCode = str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-IN/NEX/{$monthRoman}/{$year}";

                StockTransaction::create([
                    'product_id'       => $grItem->product_id,
                    'warehouse_id'     => $receipt->warehouse_id,
                    'transaction_code' => $txCode,
                    'reference_number' => $receipt->gr_number,
                    'mutation_type'    => 'stock_in',
                    'transaction_date' => $receipt->receipt_date,
                    'quantity'         => $qty,
                    'price'            => $unitPrice,
                    'total_price'      => $total,
                    'notes'            => "Penerimaan GR {$receipt->gr_number} (PO {$po->po_number})",
                    'created_by'       => $receipt->received_by,
                    'reference_id'     => $receipt->id,
                    'reference_type'   => \App\Models\Procurement\GoodsReceipt::class,
                ]);

                $seq++;

                // ===== CREATE SERIAL NUMBER =====
                if (!empty($sns)) {

                    $serialData = [];

                    foreach ($sns as $sn) {
                        $serialData[] = [
                            'product_id'          => $grItem->product_id,
                            'warehouse_id'        => $receipt->warehouse_id,
                            'serial_number'       => $sn,
                            'status'              => ProductSerial::STATUS_AVAILABLE,
                            'supplier_id'         => $receipt->supplier_id,
                            'purchase_order_id'   => $po->id,
                            'customer_id'         => null,
                            'inbound_date'        => $receipt->receipt_date,
                            'outbound_date'       => null,
                            'warranty_expired_at' => now()->addYear(), // 👉 default 1 tahun
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ];
                    }

                    ProductSerial::insert($serialData);
                }

                // ===== CEK COMPLETED =====
                if ($poItem->quantity_received < $poItem->quantity) {
                    $allCompleted = false;
                }
            }

            // ===== FINANCE =====
            if ($totalReceivedValueToday > 0) {
                $taxRate = $po->subtotal > 0 ? ($po->tax_amount / $po->subtotal) : 0;
                $hutangBaru = $totalReceivedValueToday * (1 + $taxRate);

                FinancialRecord::create([
                    'transaction_date' => $receipt->receipt_date,
                    'type'             => 'hutang',
                    'amount'           => $hutangBaru,
                    'category'         => 'Accounts Payable',
                    'description'      => "Hutang GR {$receipt->gr_number} (PO {$po->po_number})",
                    'reference_number' => $receipt->gr_number,
                    'reference_type'   => \App\Models\Procurement\GoodsReceipt::class,
                    'reference_id'     => $receipt->id,
                    'created_by'       => $receipt->received_by,
                ]);
            }

            // ===== UPDATE STATUS =====
            $receipt->update([
                'status' => \App\Models\Procurement\GoodsReceipt::STATUS_COMPLETED,
            ]);

            $po->update([
                'status' => $allCompleted ? 'completed' : 'partial',
            ]);
        });
    }
}
