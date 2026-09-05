<?php

namespace App\Services\AfterSales;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnWorkflowService
{
    /**
     * Mulai servis internal — tandai RMA sedang dikerjakan teknisi.
     * Hanya berlaku dari status RECEIVED.
     */
    public function startInternalRepair(ReturnRequest $record, ?string $notes = null): void
    {
        DB::transaction(function () use ($record, $notes) {
            $record->update([
                'status'         => ReturnRequest::STATUS_INTERNAL_REPAIR,
                'internal_notes' => $notes
                    ? (($record->internal_notes ?? '') . "\n[MULAI SERVIS] " . $notes)
                    : $record->internal_notes,
            ]);
        });
    }

    /**
     * Selesaikan servis internal setelah pengerjaan.
     * Hanya berlaku dari status INTERNAL_REPAIR.
     *
     * Resolution:
     *  - repair_and_return : unit diperbaiki, SN kembali ke customer
     *  - replacement       : unit diganti dari gudang (SN baru) atau stok non-series
     *  - no_fault_found    : tidak ditemukan kerusakan, unit kembali apa adanya
     */
    public function completeInternalRepair(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $resolution = $data['resolution_type'];
            $newSnId = null;

            if ($resolution === ReturnRequest::RESOLUTION_REPAIR_AND_RETURN) {
                $this->restoreToCustomer($record);
            }

            if ($resolution === ReturnRequest::RESOLUTION_REPLACEMENT) {
                $newSnId = $this->allocateReplacement($record, $data['new_serial_number_id'] ?? null);
            }

            if ($resolution === ReturnRequest::RESOLUTION_NO_FAULT_FOUND) {
                $this->restoreToCustomer($record);
            }

            // Catat transaksi stok untuk replacement (unit serialized keluar ke customer)
            if ($newSnId) {
                $newSn = SerialNumber::find($newSnId);
                if ($newSn) {
                    $this->logStockTransaction(
                        rma: $record,
                        sn: $newSn,
                        mutationType: 'delivery',
                        type: 'keluar',
                        prefix: 'RMA-OUT-',
                        notes: "Ganti unit Return Internal untuk No: {$record->rma_number}"
                    );
                }
            }

            $record->update([
                'status'               => ReturnRequest::STATUS_READY_FOR_RETURN,
                'resolution_type'      => $resolution,
                'new_serial_number_id' => $newSnId,
                'internal_notes'       => $data['internal_notes'] ?? null,
                'warranty_decision'    => $data['warranty_decision'] ?? null,
            ]);
        });
    }

    /**
     * Kembalikan unit SN ke status SOLD (milik customer).
     * Dipakai untuk repair selesai atau no-fault-found.
     */
    private function restoreToCustomer(ReturnRequest $record): void
    {
        $serial = $record->serialNumber;
        if ($serial) {
            $serial->update(['status' => SerialNumber::STATUS_SOLD]);
        }
    }

    /**
     * Alokasikan unit pengganti dari stok gudang.
     * Serialized: SN baru dari AVAILABLE → SOLD ke customer.
     * Non-serialized: kurangi stok gudang lewat ProductStock.
     * @return int|null id SN pengganti (jika serialized)
     */
    private function allocateReplacement(ReturnRequest $record, $newSerialNumberId): ?int
    {
        if ($record->serial_number_id) {
            $oldSn = $record->serialNumber;
            if ($oldSn) {
                $oldSn->update([
                    'status' => SerialNumber::STATUS_DEFECTIVE,
                    'outbound_date' => null,
                ]);
            }

            $newSn = SerialNumber::findOrFail($newSerialNumberId);
            $newSn->update([
                'status'        => SerialNumber::STATUS_SOLD,
                'customer_id'   => $record->customer_id,
                'outbound_date' => now(),
            ]);

            return $newSn->id;
        }

        if ($record->product_id) {
            $stock = \App\Models\Inventory\ProductStock::where('product_id', $record->product_id)
                ->where('qty_available', '>', 0)
                ->orderByDesc('qty_available')
                ->first();

            if ($stock) {
                $qty = (int) ($record->qty ?? 1);
                $stock->decrement('qty_available', $qty);
                $stock->increment('sold_stock', $qty);
            }
        }

        return null;
    }

    /**
     * Memproses penerimaan barang dari vendor eksternal.
     * Membuat Serial Number baru di sistem jika vendor memberikan unit pengganti (Replace).
     */
    public function receiveFromVendor(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $newSnId = null;

            if ($data['resolution_type'] === ReturnRequest::RESOLUTION_REPLACEMENT) {
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
