<?php

namespace App\Services\AfterSales;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnWorkflowService
{
    public function processInternalRepair(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $newSnId = null;

            if ($data['resolution_type'] === 'replaced') {
                // 1. Tandai SN lama sebagai barang rusak (defective) — hanya jika ada SN
                $oldSn = $record->serialNumber;
                if ($oldSn) {
                    $oldSn->update([
                        'status' => SerialNumber::STATUS_DEFECTIVE,
                        'outbound_date' => null, // Reset tanggal keluar jika ada
                    ]);
                }

                // 2. Alokasikan SN baru dari gudang ke klien
                // Menggunakan Pessimistic Locking implisit (update langsung) untuk menghindari Race Condition
                $newSn = SerialNumber::findOrFail($data['new_serial_number_id']);
                $newSn->update([
                    'status' => SerialNumber::STATUS_SOLD,
                    'customer_id' => $record->customer_id,
                    'outbound_date' => now(),
                ]);

                $newSnId = $newSn->id;

                // 3. Kurangi stok global pada produk (Penting untuk konsistensi inventaris)
                DB::table('nx_products')
                    ->where('id', $newSn->product_id)
                    ->decrement('stock', 1);

                // 4. Catat transaksi barang keluar (Delivery)
                $this->logStockTransaction(
                    rma: $record,
                    sn: $newSn,
                    mutationType: 'delivery',
                    type: 'keluar',
                    prefix: 'RMA-OUT-',
                    notes: "Ganti unit Return Internal untuk No: {$record->rma_number}"
                );
            }

            // 5. Perbarui status dokumen ReturnRequest
            $record->update([
                'status' => ReturnRequest::STATUS_READY_FOR_RETURN,
                'resolution_type' => $data['resolution_type'],
                'new_serial_number_id' => $newSnId,
                'internal_notes' => $data['internal_notes'],
            ]);
        });
    }

    /**
     * Memproses penerimaan barang dari vendor eksternal.
     * Membuat Serial Number baru di sistem jika vendor memberikan unit pengganti (Replace).
     */
    public function receiveFromVendor(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $newSnId = null;

            if ($data['resolution_type'] === 'replaced') {
                // 1. Tandai SN lama telah dikembalikan ke vendor
                $record->serialNumber->update([
                    'status' => SerialNumber::STATUS_RETURNED
                ]);

                // 2. Daftarkan SN baru yang diterima dari vendor
                $newSn = SerialNumber::create([
                    'product_id' => $record->serialNumber->product_id,
                    'warehouse_id' => $record->serialNumber->warehouse_id,
                    'serial_number' => $data['new_serial_number'],
                    'status' => SerialNumber::STATUS_SOLD, // Langsung dialokasikan untuk klien
                    'customer_id' => $record->customer_id,
                    'inbound_date' => now(),
                    'outbound_date' => now(),
                ]);

                $newSnId = $newSn->id;

                // 3. Catat transaksi barang masuk (Stock In)
                $this->logStockTransaction(
                    rma: $record,
                    sn: $newSn,
                    mutationType: 'stock_in',
                    type: 'masuk',
                    prefix: 'RMA-VEND-IN-',
                    notes: "Terima unit pengganti dari Vendor untuk Return: {$record->rma_number}"
                );

            }

            // 4. Perbarui status dokumen ReturnRequest
            $record->update([
                'status' => ReturnRequest::STATUS_READY_FOR_RETURN,
                'back_from_vendor_date' => now(),
                'resolution_type' => $data['resolution_type'],
                'new_serial_number_id' => $newSnId,
                'vendor_notes' => $data['vendor_notes'],
            ]);
        });
    }

    /**
     * Fungsi Helper Private untuk mencatat log mutasi stok.
     * Mengisolasi logika insert agar tidak terjadi duplikasi kode (DRY Principle).
     */
    private function logStockTransaction(
        ReturnRequest $rma,
        SerialNumber $sn,
        string $mutationType,
        string $type,
        string $prefix,
        string $notes
    ): void {
        DB::table('nx_stock_transactions')->insert([
            'reference_number' => $rma->rma_number,
            'mutation_type'    => $mutationType,
            'transaction_code' => $prefix . time(),
            'product_id'       => $sn->product_id,
            'warehouse_id'     => $sn->warehouse_id,
            'serial_number_id' => $sn->id,
            'transaction_date' => now(),
            'type'             => $type,
            'quantity'         => 1,
            'price'            => 0,
            'total_price'      => 0,
            'stock_before'     => 0,
            'stock_after'      => 0,
            'created_by'       => Auth::id() ?? 1,
            'notes'            => $notes,
            'reference_type'   => ReturnRequest::class,
            'reference_id'     => $rma->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
