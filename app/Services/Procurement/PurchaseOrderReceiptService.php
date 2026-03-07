<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber as ProductSerial;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseOrderReceiptService
{
    /**
     * Memproses penerimaan barang ke gudang beserta pencatatan Serial Number (jika ada).
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array $data Data input dari form Filament
     * @param int|null $userId ID User yang memproses
     * @throws Exception
     */
    public function processReceipt(PurchaseOrder $purchaseOrder, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($purchaseOrder, $data, $userId) {
            // 1. Lock record PO
            $lockedPo = PurchaseOrder::where('id', $purchaseOrder->id)->lockForUpdate()->first();

            $warehouseId = $data['warehouse_id'];
            $receiptDate = $data['receipt_date'];
            $allCompleted = true;

            // 2. Siapkan parameter untuk Auto-Numbering
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

            $seq = 1;
            if ($last) {
                $parts = explode('/', $last);
                $seq = ((int) $parts[0]) + 1;
            }

            // 3. Proses setiap item yang diterima
            foreach ($lockedPo->items as $item) {
                // Key untuk form input yang baru (menggunakan prefix qty_ dan sn_)
                $inputQtyKey = "qty_{$item->id}";
                $inputSnKey  = "sn_{$item->id}";

                $receivedNow = (int) ($data[$inputQtyKey] ?? 0);
                $snInput     = $data[$inputSnKey] ?? '';

                if ($receivedNow > 0) {
                    // Update qty received di tabel PO Item
                    $item->quantity_received += $receivedNow;
                    $item->save();

                    // Generate kode transaksi
                    $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                    $txCode = "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";

                    // Catat ke Stock Transaction
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

                    // LOGIKA BARU: Pencatatan Serial Number
                    // Cek jika product_id ini wajib menggunakan SN
                    if ($item->product->is_serialized ?? false) {
                        // Pecah string dari Textarea berdasarkan baris baru (enter)
                        $sns = array_filter(array_map('trim', explode("\n", $snInput)));

                        $serialData = [];
                        foreach ($sns as $sn) {
                            $serialData[] = [
                                'product_id'        => $item->product_id,
                                'serial_number'     => $sn,
                                'warehouse_id'      => $warehouseId,
                                'status'            => 'available', // Status ready untuk dijual/dikirim
                                'purchase_order_id' => $lockedPo->id, // Tracking asal mula SN
                                'inbound_date'      => $receiptDate,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                        }

                        // Insert massal agar optimal di database
                        if (!empty($serialData)) {
                            ProductSerial::insert($serialData);
                        }
                    }
                }

                // Cek apakah PO item ini masih partial atau sudah terpenuhi total
                if ($item->quantity_received < $item->quantity) {
                    $allCompleted = false;
                }
            }

            // 4. Update status final PO
            $lockedPo->status = $allCompleted ? 'completed' : 'partial';
            $lockedPo->save();
        });
    }
}
