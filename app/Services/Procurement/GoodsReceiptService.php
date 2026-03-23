<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber as ProductSerial;
use App\Models\Finance\FinancialRecord;
use Illuminate\Support\Facades\DB;
use Exception;

class GoodsReceiptService
{
    /**
     * Di-trigger oleh GoodsReceiptResource saat data berhasil disimpan (Created)
     */
    public function processAfterCreation(\App\Models\Procurement\GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            // Lock PO untuk mencegah race condition
            $po = PurchaseOrder::where('id', $receipt->purchase_order_id)->lockForUpdate()->first();
            $allCompleted = true;
            $totalReceivedValueToday = 0;

            // Generate Tx Code untuk Stock
            $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $monthRoman = $romanMonths[now()->month - 1];
            $year = now()->year;
            $company = 'NEX';

            $lastTx = StockTransaction::where('transaction_code', 'like', "%/ST-IN/{$company}/{$monthRoman}/{$year}")
                        ->orderByDesc('id')->lockForUpdate()->value('transaction_code');
            $seq = $lastTx ? ((int) explode('/', $lastTx)[0]) + 1 : 1;

            // Loop item yang diterima orang gudang
            foreach ($receipt->items as $grItem) {
                if ($grItem->quantity_received > 0) {

                    // 1. Update PO Item
                    $poItem = PurchaseOrderItem::find($grItem->purchase_order_item_id);
                    $poItem->increment('quantity_received', $grItem->quantity_received);

                    // 2. Kalkulasi nilai hutang (Hidden dari Gudang, diurus Service)
                    // HPP = qty_received * harga satuan di PO
                    $totalReceivedValueToday += ($grItem->quantity_received * $poItem->unit_price);

                    // 3. Catat Transaksi Stok
                    $txCode = str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-IN/{$company}/{$monthRoman}/{$year}";
                    StockTransaction::create([
                        'product_id'       => $grItem->product_id,
                        'warehouse_id'     => $receipt->warehouse_id,
                        'transaction_code' => $txCode,
                        'reference_number' => $receipt->gr_number, // Referensi ke Surat Penerimaan
                        'mutation_type'    => 'stock_in',
                        'transaction_date' => $receipt->receipt_date,
                        'quantity'         => $grItem->quantity_received,
                        'price'            => $poItem->unit_price,
                        'notes'            => 'Penerimaan GR: ' . $receipt->gr_number . ' dari PO: ' . $po->po_number,
                        'created_by'       => $receipt->received_by,
                    ]);
                    $seq++;

                    // 4. Catat Serial Number jika ada
                    if (!empty($grItem->scanned_sns)) {
                        $sns = array_filter(array_map('trim', explode("\n", $grItem->scanned_sns)));
                        $serialData = [];
                        foreach ($sns as $sn) {
                            $serialData[] = [
                                'product_id'        => $grItem->product_id,
                                'serial_number'     => $sn,
                                'warehouse_id'      => $receipt->warehouse_id,
                                'status'            => 'available',
                                'purchase_order_id' => $po->id,
                                'inbound_date'      => $receipt->receipt_date,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                        }
                        ProductSerial::insert($serialData);
                    }
                }

                // Cek apakah PO item masih kurang
                $checkItem = PurchaseOrderItem::find($grItem->purchase_order_item_id);
                if ($checkItem->quantity_received < $checkItem->quantity) {
                    $allCompleted = false;
                }
            }

            // 5. Catat Hutang Dagang ke Finance
            if ($totalReceivedValueToday > 0) {
                // Proporsi Pajak
                $taxRate = $po->subtotal > 0 ? ($po->tax_amount / $po->subtotal) : 0;
                $taxNominal = $totalReceivedValueToday * $taxRate;
                $hutangBaru = $totalReceivedValueToday + $taxNominal;

                FinancialRecord::create([
                    'transaction_date' => $receipt->receipt_date,
                    'type'             => 'hutang',
                    'amount'           => $hutangBaru,
                    'category'         => 'Accounts Payable',
                    'description'      => 'Hutang dagang (GR) via: ' . $receipt->gr_number . ' (Ref PO: ' . $po->po_number . ')',
                    'reference_number' => $receipt->gr_number,
                    'reference_type'   => \App\Models\Procurement\GoodsReceipt::class,
                    'reference_id'     => $receipt->id,
                    'created_by'       => $receipt->received_by,
                ]);
            }

            // 6. Update Status GR & PO
            $receipt->update(['status' => 'completed']);
            $po->update(['status' => $allCompleted ? 'completed' : 'partial']);
        });
    }
}
