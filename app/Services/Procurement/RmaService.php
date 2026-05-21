<?php

namespace App\Services;

use App\Models\Procurement\RmaRequest;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use Illuminate\Support\Facades\DB;

class RmaService
{
    public function receiveFromVendor(RmaRequest $rma, array $data): void
    {
        DB::transaction(function () use ($rma, $data) {
            $newSnId = null;

            if ($data['resolution_type'] === 'replaced') {
                // 1. Matikan SN lama
                $rma->serialNumber->update(['status' => 'returned_to_vendor']);

                // 2. Buat SN Baru
                $newSn = SerialNumber::create([
                    'product_id' => $rma->serialNumber->product_id,
                    'warehouse_id' => $rma->serialNumber->warehouse_id,
                    'serial_number' => $data['new_serial_number'],
                    'status' => 'sold', // langsung keluar karena ini hak klien
                    'customer_id' => $rma->customer_id,
                    'inbound_date' => now(),
                    'outbound_date' => now(),
                ]);
                $newSnId = $newSn->id;

                // 3. Tembak ke model StockTransaction buatan teman Anda
                $this->createStockTransaction(
                    $rma,
                    $newSn,
                    'masuk',
                    'rma_in_vendor',
                    "Terima unit pengganti RMA Vendor No: {$rma->rma_number}"
                );
            }

            $rma->update([
                'status' => RmaRequest::STATUS_READY_FOR_RETURN,
                'back_from_vendor_date' => now(),
                'resolution_type' => $data['resolution_type'],
                'new_serial_number_id' => $newSnId,
                'vendor_notes' => $data['vendor_notes'],
            ]);
        });
    }

    public function processInternal(RmaRequest $rma, array $data): void
    {
        DB::transaction(function () use ($rma, $data) {
            $newSnId = null;

            if ($data['resolution_type'] === 'replaced') {
                $rma->serialNumber->update(['status' => 'defective']);

                // Ambil SN pengganti dari gudang
                $newSn = SerialNumber::findOrFail($data['new_serial_number_id']);
                $newSn->update([
                    'status' => 'sold',
                    'customer_id' => $rma->customer_id,
                    'outbound_date' => now(),
                ]);
                $newSnId = $newSn->id;

                // Potong stok fisik product utama
                DB::table('nx_products')->where('id', $newSn->product_id)->decrement('qty_available', 1);

                // Tembak ke model StockTransaction
                $this->createStockTransaction(
                    $rma,
                    $newSn,
                    'keluar',
                    'rma_out_internal',
                    "Ganti unit RMA Internal No: {$rma->rma_number}"
                );
            }

            $rma->update([
                'status' => RmaRequest::STATUS_READY_FOR_RETURN,
                'resolution_type' => $data['resolution_type'],
                'new_serial_number_id' => $newSnId,
                'internal_notes' => $data['internal_notes'],
            ]);
        });
    }

    private function createStockTransaction(RmaRequest $rma, SerialNumber $sn, string $type, string $mutationType, string $notes): void
    {
        // Sesuaikan dengan struktur tabel teman Anda
        StockTransaction::create([
            'transaction_code' => strtoupper($type) . '-RMA-' . time(),
            'reference_number' => $rma->rma_number,
            'transaction_date' => now(),
            'product_id' => $sn->product_id,
            'warehouse_id' => $sn->warehouse_id,
            'type' => $type, // 'masuk' atau 'keluar'
            'mutation_type' => $mutationType,
            'quantity' => 1,
            'price' => 0,
            'total_price' => 0,
            'created_by' => auth()->id(),
            'notes' => $notes,
        ]);
    }
}
