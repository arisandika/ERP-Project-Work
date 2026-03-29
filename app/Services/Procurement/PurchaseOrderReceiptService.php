<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber as ProductSerial;
use App\Models\Finance\FinancialRecord; // Tambahkan ini
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseOrderReceiptService
{
    public function processReceipt(PurchaseOrder $purchaseOrder, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($purchaseOrder, $data, $userId) {
            $lockedPo = PurchaseOrder::where('id', $purchaseOrder->id)->lockForUpdate()->first();

            $warehouseId = $data['warehouse_id'];
            $receiptDate = $data['receipt_date'];
            $allCompleted = true;

            // Variabel untuk menampung total nominal barang yang masuk hari ini
            $totalReceivedValueToday = 0;

            $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $monthRoman = $romanMonths[now()->month - 1];
            $year = now()->year;
            $company = 'NEX';
            $code = 'ST-IN';
            $prefixLike = "%/{$code}/{$company}/{$monthRoman}/{$year}";

            $last = StockTransaction::query()
                ->where('transaction_code', 'like', $prefixLike)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('transaction_code');

            $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

            foreach ($lockedPo->items as $item) {
                $inputQtyKey = "qty_{$item->id}";
                $inputSnKey  = "sn_{$item->id}";

                $receivedNow = (int) ($data[$inputQtyKey] ?? 0);
                $snInput     = $data[$inputSnKey] ?? '';

                if ($receivedNow > 0) {
                    $item->quantity_received += $receivedNow;
                    $item->save();

                    // Kalkulasi nilai hutang baru (Qty Diterima x Harga Satuan)
                    $totalReceivedValueToday += ($receivedNow * $item->unit_price);

                    $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                    $txCode = "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";

                    StockTransaction::create([
                        'product_id'       => $item->product_id,
                        'warehouse_id'     => $warehouseId,
                        'transaction_code' => $txCode,
                        'reference_number' => $lockedPo->po_number,
                        'mutation_type'    => 'stock_in',
                        'transaction_date' => $receiptDate,
                        'quantity'         => $receivedNow,
                        'price'            => $item->unit_price,
                        'notes'            => 'Penerimaan otomatis dari ' . $lockedPo->po_number,
                        'created_by'       => $userId ?? 1,
                    ]);

                    $seq++;

                    if ($item->product->is_serialized ?? false) {
                        $sns = array_filter(array_map('trim', explode("\n", $snInput)));
                        $serialData = [];
                        foreach ($sns as $sn) {
                            $serialData[] = [
                                'product_id'        => $item->product_id,
                                'serial_number'     => $sn,
                                'warehouse_id'      => $warehouseId,
                                'status'            => 'available',
                                'purchase_order_id' => $lockedPo->id,
                                'inbound_date'      => $receiptDate,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                        }
                        if (!empty($serialData)) {
                            ProductSerial::insert($serialData);
                        }
                    }
                }

                if ($item->quantity_received < $item->quantity) {
                    $allCompleted = false;
                }
            }

            // --- REQUIREMENT PERUSAHAAN: INTEGRASI FINANCE (HUTANG DAGANG) ---
            if ($totalReceivedValueToday > 0) {
                // Tambahkan proporsi pajak jika ada (Berdasarkan rate pajak PO)
                $taxRate = $lockedPo->subtotal > 0 ? ($lockedPo->tax_amount / $lockedPo->subtotal) : 0;
                $taxNominal = $totalReceivedValueToday * $taxRate;
                $hutangBaru = $totalReceivedValueToday + $taxNominal;

                FinancialRecord::create([
                    'transaction_date' => $receiptDate,
                    'type'             => 'hutang', // Kategori Hutang Dagang
                    'amount'           => $hutangBaru,
                    'category'         => 'Accounts Payable',
                    'description'      => 'Hutang dagang atas penerimaan barang (GR) dari PO: ' . $lockedPo->po_number,
                    'reference_number' => $lockedPo->po_number,
                    'reference_type'   => PurchaseOrder::class,
                    'reference_id'     => $lockedPo->id,
                    'created_by'       => $userId ?? 1,
                ]);
            }

            $lockedPo->status = $allCompleted ? 'completed' : 'partial';
            $lockedPo->save();
        });
    }
}
