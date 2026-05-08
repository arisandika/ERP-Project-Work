<?php

namespace App\Services\Finance;

use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseReturnFinancialService
{
    /**
     * Mengeksekusi pencatatan jurnal keuangan berdasarkan tipe penyelesaian retur.
     *
     * @param PurchaseReturn $purchaseReturn
     * @throws Exception
     */
    public function execute(PurchaseReturn $purchaseReturn): void
    {
        // 1. Kalkulasi total nilai retur dari item
        $totalAmount = $purchaseReturn->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        if ($totalAmount <= 0) {
            throw new Exception("Total nilai retur tidak valid (Nol).");
        }

        DB::transaction(function () use ($purchaseReturn, $totalAmount) {

            // 2. Tentukan parameter berdasarkan resolusi
            if ($purchaseReturn->resolution_type === 'credit_note') {
                /*
                 * POTONG HUTANG (CREDIT NOTE)
                 * Berdasarkan logic AP Anda, untuk mengurangi hutang, kita harus membuat
                 * record 'pengeluaran' dengan category 'Accounts Payable'.
                 * PENTING: reference_number harus sama dengan nomor invoice/hutang asli.
                 */

                // Ambil nomor referensi hutang asli (Asumsi diambil dari relasi GoodsReceipt -> Invoice)
                // Jika sistem Anda belum memetakan ini, sementara kita gunakan format fallback
                $originalInvoiceRef = $purchaseReturn->goodsReceipt?->purchaseOrder?->po_number
                                      ?? $purchaseReturn->return_number;

                FinancialRecord::create([
                    'type' => 'pengeluaran',
                    'amount' => $totalAmount,
                    'category' => 'Accounts Payable', // Wajib persis seperti logic pengecekan di Filament Page Anda
                    'description' => "Potong Hutang (Credit Note) dari Retur Pembelian: {$purchaseReturn->return_number}",
                    'reference_number' => $originalInvoiceRef,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                ]);

            } elseif ($purchaseReturn->resolution_type === 'refund') {
                /*
                 * REFUND DANA TUNAI
                 * Uang kembali ke perusahaan, sehingga menjadi 'pemasukan'.
                 */
                FinancialRecord::create([
                    'type' => 'pemasukan',
                    'amount' => $totalAmount,
                    'category' => 'Purchase Return Refund', // Akan masuk ke 'other_income' atau 'revenue' berdasarkan logic guessAccountingFields
                    'description' => "Refund Tunai dari Retur Pembelian: {$purchaseReturn->return_number}",
                    'reference_number' => $purchaseReturn->return_number,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }

            // 3. Update status retur menjadi completed
            $purchaseReturn->update(['status' => 'completed']);
        });
    }
}
