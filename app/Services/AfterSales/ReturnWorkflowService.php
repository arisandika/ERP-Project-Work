<?php

namespace App\Services\AfterSales;

use App\Models\AfterSales\InternalRepair;
use App\Models\AfterSales\ReturnRequest;
use App\Models\AfterSales\VendorClaim;
use App\Models\Inventory\SerialNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Workflow inti setelah-sales RMA. Seluruh jalur yang memasuki READY_FOR_RETURN
 * dijaga precondition statusnya (assertStatus) — pelanggaran melempar exception
 * dan membatalkan transaksi (rollback-safe).
 *
 * Penyelesaian InternalRepair / VendorClaim divalidasi post-state SN-nya
 * SEBELUM RMA diizinkan menjadi READY_FOR_RETURN — jangan menutup resolusi
 * tanpa unit yang benar-benar kembali ke klien. 'Kembali ke klien' berarti
 * SN berstatus SOLD, dan itu hanya boleh dicapai lewat transisi yang diizinkan
 * state machine (DEFECTIVE→SOLD, RETURNED→SOLD) — bukan asumsi status.
 *
 * SN dipindahkan HANYA lewat SerialNumber::transitionTo() (whitelist + throw).
 */
class ReturnWorkflowService
{
    /**
     * Guard status dokumen RMA sebelum operasi berjalan.
     *
     * @throws \InvalidArgumentException bila status tidak sesuai konteks.
     */
    private function assertStatus(ReturnRequest $record, string $expected, string $context): void
    {
        if ($record->status !== $expected) {
            throw new \InvalidArgumentException(
                "[{$context}] RMA {$record->rma_number} harus berstatus '{$expected}', saat ini '{$record->status}'."
            );
        }
    }

    /**
     * Kembalikan unit ke status SOLD (milik customer) lewat transitionTo —
     * state machine melempar bila status sekarang tidak mengizinkan transisi ke SOLD.
     * Setelahnya divalidasi post-state; gagal → exception → rollback.
     *
     * @throws \InvalidArgumentException bila SN tidak bisa kembali ke SOLD.
     */
    private function resumeUnitToClient(ReturnRequest $record, string $context): void
    {
        $serial = $record->serialNumber;
        if (! $serial || $serial->status !== SerialNumber::STATUS_SOLD) {
            if ($serial) {
                SerialNumber::transitionTo($serial, SerialNumber::STATUS_SOLD);
            }
        }
        $this->assertPostState(
            $record,
            ReturnRequest::RESOLUTION_REPAIR_AND_RETURN,
            null,
            SerialNumber::STATUS_SOLD,
            $context
        );
    }

    /**
     * Validasi hasil resolusi sebelum RMA menjadi READY_FOR_RETURN.
     * Lempar exception bila SN tidak berada di status yang disyaratkan —
     * transaksi terluar di-rollback, RMA TIDAK pindah ke READY_FOR_RETURN.
     *
     * @param string $resolution       jenis penyelesaian yang dipilih
     * @param int|null $newSnId        id SN pengganti (jika replacement)
     * @param string $expectedOldStatus status SN lama pasca-replacement
     *                                  (DEFECTIVE internal / RETURNED vendor)
     */
    private function assertPostState(
        ReturnRequest $record,
        string $resolution,
        ?int $newSnId,
        string $expectedOldStatus,
        string $context
    ): void {
        // Unit yang sama kembali ke klien — SN harus tetap milik customer (SOLD).
        if (in_array($resolution, [
            ReturnRequest::RESOLUTION_REPAIR_AND_RETURN,
            ReturnRequest::RESOLUTION_NO_FAULT_FOUND,
        ], true)) {
            $serial = $record->serialNumber;
            if (! $serial || $serial->status !== SerialNumber::STATUS_SOLD) {
                throw new \InvalidArgumentException(
                    "[{$context}] SN unit RMA {$record->rma_number} harus berstatus SOLD"
                    . " untuk kembali ke klien, saat ini '" . ($serial?->status ?? 'kosong') . "'."
                );
            }
            return;
        }

        if ($resolution === ReturnRequest::RESOLUTION_REPLACEMENT) {
            // SN lama: dipindah dari SOLD → DEFECTIVE (internal) / RETURNED (vendor).
            $serial = $record->serialNumber;
            if ($serial && $serial->status !== $expectedOldStatus) {
                throw new \InvalidArgumentException(
                    "[{$context}] SN lama RMA {$record->rma_number} harus berstatus '{$expectedOldStatus}',"
                    . " saat ini '{$serial->status}'."
                );
            }

            // SN pengganti harus sudah benar-benar milik klien (SOLD).
            if (! $newSnId) {
                throw new \InvalidArgumentException(
                    "[{$context}] Replacement RMA {$record->rma_number} belum mengalokasikan SN pengganti."
                );
            }
            $newSn = SerialNumber::find($newSnId);
            if (! $newSn || $newSn->status !== SerialNumber::STATUS_SOLD) {
                throw new \InvalidArgumentException(
                    "[{$context}] SN pengganti RMA {$record->rma_number} harus berstatus SOLD"
                    . " (milik klien), saat ini '" . ($newSn?->status ?? 'kosong') . "'."
                );
            }
        }
    }

    /**
     * Mulai servis internal — tandai RMA + buat entri InternalRepair.
     * Wajib dari status RECEIVED dan belum ada servis aktif.
     */
    public function startInternalRepair(ReturnRequest $record, ?string $notes = null): void
    {
        DB::transaction(function () use ($record, $notes) {
            $this->assertStatus($record, ReturnRequest::STATUS_RECEIVED, 'startInternalRepair');

            if ($record->internalRepair &&
                $record->internalRepair->status !== InternalRepair::STATUS_COMPLETED) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} sudah memiliki servis internal aktif."
                );
            }

            $record->update([
                'status'         => ReturnRequest::STATUS_INTERNAL_REPAIR,
                'internal_notes' => $notes
                    ? (($record->internal_notes ?? '') . "\n[MULAI SERVIS] " . $notes)
                    : $record->internal_notes,
            ]);

            // Buat entitas InternalRepair terpisah — lifecycle servis dipantau di tabel baru.
            InternalRepair::create([
                'rma_id'            => $record->id,
                'status'            => InternalRepair::STATUS_PENDING,
                'resolution_type'   => $record->resolution_type,
                'notes'             => $notes,
                'created_by'        => Auth::id(),
            ]);
        });
    }

    /**
     * Selesaikan servis internal — RMA ke READY_FOR_RETURN.
     * Wajib dari status INTERNAL_REPAIR dengan entri servis belum ditutup.
     *
     * Resolution:
     *  - repair_and_return : unit diperbaiki, SN kembali ke customer (tetap SOLD)
     *  - replacement       : unit diganti dari gudang (SN baru AVAILABLE→SOLD), SN lama → DEFECTIVE
     *  - no_fault_found    : tidak ditemukan kerusakan, unit kembali apa adanya (tetap SOLD)
     */
    public function completeInternalRepair(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $this->assertStatus($record, ReturnRequest::STATUS_INTERNAL_REPAIR, 'completeInternalRepair');

            if (! $record->internalRepair ||
                $record->internalRepair->status !== InternalRepair::STATUS_IN_PROGRESS) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} tidak memiliki servis internal yang sedang dikerjakan."
                );
            }

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

            // VALIDASI: hasil servis + post-state SN harus sah SEBELUM RMA → READY_FOR_RETURN.
            $this->assertPostState(
                $record,
                $resolution,
                $newSnId,
                SerialNumber::STATUS_DEFECTIVE,
                'completeInternalRepair'
            );

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

            // Tutup entri InternalRepair terkait.
            $record->internalRepair->update([
                'status'          => InternalRepair::STATUS_COMPLETED,
                'resolution_type' => $resolution,
                'completed_at'    => now(),
            ]);
        });
    }

    /**
     * Kembalikan unit SN ke status SOLD (milik customer).
     * Dipakai untuk repair selesai atau no-fault-found — SN yang tak pernah
     * dipindah dari SOLD tidak perlu transisi.
     */
    private function restoreToCustomer(ReturnRequest $record): void
    {
        $serial = $record->serialNumber;
        if ($serial && $serial->status !== SerialNumber::STATUS_SOLD) {
            SerialNumber::transitionTo($serial, SerialNumber::STATUS_SOLD);
        }
    }

    /**
     * Alokasikan unit pengganti dari stok gudang.
     *
     * Serialized:    SN lama SOLD → DEFECTIVE; SN baru AVAILABLE → SOLD (milik customer).
     * Non-serialized: kurangi qty_available stok gudang.
     *
     * Guard: replacement serialized WAJIB SN pengganti valid (AVAILABLE),
     * non-serialized WAJIB stok tersedia — else throw (jangan menutup RMA tanpa unit).
     *
     * @return int|null id SN pengganti (jika serialized)
     */
    private function allocateReplacement(ReturnRequest $record, $newSerialNumberId): ?int
    {
        if ($record->serial_number_id) {
            if (! $newSerialNumberId) {
                throw new \InvalidArgumentException(
                    "Replacement serialized butuh SN unit pengganti untuk RMA {$record->rma_number}."
                );
            }

            $oldSn = $record->serialNumber;
            if ($oldSn) {
                SerialNumber::transitionTo($oldSn, SerialNumber::STATUS_DEFECTIVE, [
                    'outbound_date' => null,
                ]);
            }

            $newSn = SerialNumber::findOrFail($newSerialNumberId);
            // transitionTo throw bila SN pengganti bukan AVAILABLE (whitelist AVAILABLE→SOLD).
            SerialNumber::transitionTo($newSn, SerialNumber::STATUS_SOLD, [
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

            if (! $stock) {
                throw new \InvalidArgumentException(
                    "Tidak ada stok tersedia untuk replacement non-serialized RMA {$record->rma_number}."
                );
            }

            $qty = (int) ($record->qty ?? 1);
            $stock->decrement('qty_available', $qty);
            $stock->increment('sold_stock', $qty);
        }

        return null;
    }

    /**
     * Memproses penerimaan barang dari vendor eksternal — RMA ke READY_FOR_RETURN.
     * Wajib dari status SENT_TO_VENDOR dengan klaim vendor berstatus sent.
     *
     * Replacement:      SN lama SOLD → RETURNED (unit dikembalikan ke vendor);
     *                   SN baru vendor didaftarkan AVAILABLE → SOLD (langsung milik customer).
     * repair_and_return: unit yang sama kembali ke customer (SN tetap SOLD, tanpa transisi).
     */
    public function receiveFromVendor(ReturnRequest $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $this->assertStatus($record, ReturnRequest::STATUS_SENT_TO_VENDOR, 'receiveFromVendor');

            if (! $record->vendorClaim || $record->vendorClaim->status !== VendorClaim::STATUS_SENT) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} tidak memiliki klaim vendor berstatus sent."
                );
            }

            $newSnId = null;

            if ($data['resolution_type'] === ReturnRequest::RESOLUTION_REPLACEMENT) {
                $newSerialNumber = $data['new_serial_number'] ?? null;
                if (! $newSerialNumber) {
                    throw new \InvalidArgumentException(
                        "Replacement dari vendor butuh nomor SN baru untuk RMA {$record->rma_number}."
                    );
                }
                if (SerialNumber::where('serial_number', $newSerialNumber)->exists()) {
                    throw new \InvalidArgumentException(
                        "SN '{$newSerialNumber}' sudah terdaftar di sistem."
                    );
                }

                // 1. Tandai SN lama telah dikembalikan ke vendor (SOLD → RETURNED).
                SerialNumber::transitionTo($record->serialNumber, SerialNumber::STATUS_RETURNED);

                // 2. Daftarkan SN baru dari vendor: AVAILABLE dulu, lalu → SOLD.
                $newSn = SerialNumber::create([
                    'product_id'     => $record->serialNumber->product_id,
                    'warehouse_id'   => $record->serialNumber->warehouse_id,
                    'serial_number'  => $newSerialNumber,
                    'status'         => SerialNumber::STATUS_AVAILABLE,
                    'customer_id'    => $record->customer_id,
                    'inbound_date'   => now(),
                ]);

                SerialNumber::transitionTo($newSn, SerialNumber::STATUS_SOLD, [
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

            // 4. VALIDASI: hasil klaim + post-state SN harus sah SEBELUM RMA → READY_FOR_RETURN.
            $this->assertPostState(
                $record,
                $data['resolution_type'],
                $newSnId,
                SerialNumber::STATUS_RETURNED,
                'receiveFromVendor'
            );

            // 5. Tutup klaim vendor terkait.
            $record->vendorClaim->update([
                'status'      => VendorClaim::STATUS_COMPLETED,
                'received_at' => now(),
            ]);

            // 6. Perbarui status dokumen ReturnRequest → READY_FOR_RETURN.
            $record->update([
                'status'                 => ReturnRequest::STATUS_READY_FOR_RETURN,
                'back_from_vendor_date'  => now(),
                'resolution_type'        => $data['resolution_type'],
                'new_serial_number_id'   => $newSnId,
                'vendor_notes'           => $data['vendor_notes'],
            ]);
        });
    }

    /**
     * Klaim vendor DITOLAK supplier. Ini hanya keputusan garansi — unit masih
     * berada di vendor, BELUM kembali. RMA tetap SENT_TO_VENDOR; Turunan
     * returnRejectedUnit() yang memindahkan ke READY_FOR_RETURN saat unit fisik
     * benar-benar diterima kembali.
     *
     * Wajib dari status SENT_TO_VENDOR dengan klaim vendor berstatus sent.
     * SN TIDAK dipindahkan di sini.
     */
    public function rejectVendorClaim(ReturnRequest $record, ?string $reason = null): void
    {
        DB::transaction(function () use ($record, $reason) {
            $this->assertStatus($record, ReturnRequest::STATUS_SENT_TO_VENDOR, 'rejectVendorClaim');

            if (! $record->vendorClaim || $record->vendorClaim->status !== VendorClaim::STATUS_SENT) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} tidak memiliki klaim vendor berstatus sent."
                );
            }

            $record->vendorClaim->update([
                'status'       => VendorClaim::STATUS_REJECTED,
                'vendor_notes' => $reason ?? $record->vendorClaim->vendor_notes,
            ]);
        });
    }

    /**
     * Unit yang ditolak vendor benar-benar DITERIMA KEMBALI (fisik kembali ke
     * toko). Satu-satunya titik vendor-rejection ~> READY_FOR_RETURN —
     * penolakan klaim TIDAK otomatis berarti unit sudah kembali.
     *
     * Wajib dari status SENT_TO_VENDOR dengan klaim vendor berstatus rejected.
     * SN kembali ke SOLD lewat transitionTo (state-machine-guarded).
     */
    public function returnRejectedUnit(ReturnRequest $record, ?string $notes = null): void
    {
        DB::transaction(function () use ($record, $notes) {
            $this->assertStatus($record, ReturnRequest::STATUS_SENT_TO_VENDOR, 'returnRejectedUnit');

            if (! $record->vendorClaim || $record->vendorClaim->status !== VendorClaim::STATUS_REJECTED) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} tidak memiliki klaim vendor berstatus rejected."
                );
            }

            $this->resumeUnitToClient($record, 'returnRejectedUnit');

            $record->update([
                'status'                 => ReturnRequest::STATUS_READY_FOR_RETURN,
                'received_date'          => now(),
                'back_from_vendor_date'  => now(),
                'vendor_notes'           => $notes ?? $record->vendor_notes,
            ]);
        });
    }

    /**
     * Konfirmasi refund yang sudah diselesaikan Finance.
     *
     * refund_status: pending → completed, RMA: REFUND_PENDING → READY_FOR_RETURN.
     * Wajib: status REFUND_PENDING, refund_status=pending, DAN prasyarat Finance:
     * record piutang (FinancialRecord) mesti ada untuk RMA ini.
     */
    public function markRefundCompleted(ReturnRequest $record): void
    {
        DB::transaction(function () use ($record) {
            $this->assertStatus($record, ReturnRequest::STATUS_REFUND_PENDING, 'markRefundCompleted');

            if ($record->refund_status !== ReturnRequest::REFUND_PENDING) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} refund bukan berstatus pending."
                );
            }

            $hasRecord = \App\Models\Finance\FinancialRecord::where('reference_type', ReturnRequest::class)
                ->where('reference_id', $record->id)
                ->where('type', 'piutang')
                ->exists();
            if (! $hasRecord) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} belum memiliki record refund (piutang) di modul Finance."
                );
            }

            $record->update([
                'refund_status' => ReturnRequest::REFUND_COMPLETED,
                'status'        => ReturnRequest::STATUS_READY_FOR_RETURN,
            ]);
        });
    }

    /**
     * Garansi ditolak TIDAK menutup RMA — unit fisik sudah diterima, tetap harus
     * kembali ke customer. WARRANTY_REJECTED → READY_FOR_RETURN, lalu ditutup
     * via return_to_client.
     *
     * SN kembali ke SOLD lewat transitionTo (state-machine-guarded), lalu
     * divalidasi post-state.
     */
    public function confirmWarrantyRejected(ReturnRequest $record): void
    {
        DB::transaction(function () use ($record) {
            $this->assertStatus($record, ReturnRequest::STATUS_WARRANTY_REJECTED, 'confirmWarrantyRejected');
            $this->resumeUnitToClient($record, 'confirmWarrantyRejected');

            $record->update([
                'status'                 => ReturnRequest::STATUS_READY_FOR_RETURN,
                'back_from_vendor_date'  => now(),
            ]);
        });
    }

    /**
     * Resolusi no_fault_found — unit kembali ke klien apa adanya, TANPA entity
     * servis/klaim. RECEIVED → READY_FOR_RETURN.
     *
     * Wajib: status RECEIVED + warranty_decision approved/na. SN divalidasi
     * post-state SOLD; jalur ini dijaga setara entry READY_FOR_RETURN lain.
     */
    public function resolveNoFaultFound(ReturnRequest $record): void
    {
        DB::transaction(function () use ($record) {
            $this->assertStatus($record, ReturnRequest::STATUS_RECEIVED, 'resolveNoFaultFound');

            if (! in_array($record->warranty_decision, [
                ReturnRequest::WARRANTY_APPROVED,
                ReturnRequest::WARRANTY_NA,
            ], true)) {
                throw new \InvalidArgumentException(
                    "RMA {$record->rma_number} keputusan garansi belum approved/na untuk no_fault_found."
                );
            }

            $this->assertPostState(
                $record,
                ReturnRequest::RESOLUTION_NO_FAULT_FOUND,
                null,
                SerialNumber::STATUS_SOLD,
                'resolveNoFaultFound'
            );

            $record->update([
                'status'           => ReturnRequest::STATUS_READY_FOR_RETURN,
                'resolution_type'  => ReturnRequest::RESOLUTION_NO_FAULT_FOUND,
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