<?php

namespace App\Console\Commands;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\AfterSales\ReturnWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Self-check untuk refinement RMA — dijalankan dengan:
 *   php artisan rma:selfcheck
 *
 * Memverifikasi tanpa framework test:
 *  1. SerialNumber::transitionTo() — whitelist valid + throw invalid.
 *  2. InternalRepair + VendorClaim model relasi.
 *
 * Non-trivial logic leaves one runnable check behind (lazy-dev rule).
 */
class RmaSelfCheckCommand extends Command
{
    protected $signature = 'rma:selfcheck';

    protected $description = 'Self-check refinement RMA (transitionTo + relasi baru)';

    public function handle(): int
    {
        $pass = 0;
        $fail = 0;

        $check = function (string $label, bool $ok, string $detail = '') use (&$pass, &$fail) {
            if ($ok) {
                $pass++;
                $this->info("  \u{2713} {$label}");
            } else {
                $fail++;
                $this->error("  \u{2717} {$label} {$detail}");
            }
        };

        $this->info('RMA refinement self-check');

        DB::beginTransaction();
        try {
            // Ambil product/warehouse dari SN yang sudah ada (kolom NOT NULL di skema).
            $template = SerialNumber::query()->first(['product_id', 'warehouse_id']);
            $sn = SerialNumber::create([
                'product_id'     => $template->product_id,
                'warehouse_id'   => $template->warehouse_id,
                'serial_number'  => 'SELFCHECK-' . strtoupper(uniqid()),
                'status'         => SerialNumber::STATUS_AVAILABLE,
            ]);

            // 1a. AVAILABLE → SOLD valid
            $customerId = SerialNumber::query()->whereNotNull('customer_id')->value('customer_id')
                ?? \App\Models\Crm\Customer::query()->value('id');
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_SOLD, ['customer_id' => $customerId]);
            $check('AVAILABLE→SOLD', $sn->status === SerialNumber::STATUS_SOLD, "got {$sn->status}");
            $check('extra diterapkan (customer_id)', $sn->customer_id === $customerId, "got {$sn->customer_id}");

            // 1b. SOLD → DEFECTIVE valid (replacement)
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_DEFECTIVE);
            $check('SOLD→DEFECTIVE', $sn->status === SerialNumber::STATUS_DEFECTIVE, "got {$sn->status}");

            // 1c. DEFECTIVE → SOLD valid (reject)
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_SOLD);
            $check('DEFECTIVE→SOLD (reject)', $sn->status === SerialNumber::STATUS_SOLD, "got {$sn->status}");

            // 1d. SOLD → RETURNED valid (vendor claim)
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_RETURNED);
            $check('SOLD→RETURNED', $sn->status === SerialNumber::STATUS_RETURNED, "got {$sn->status}");

            // 1e. RETURNED → SOLD valid (reject claim)
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_SOLD);
            $check('RETURNED→SOLD (reject claim)', $sn->status === SerialNumber::STATUS_SOLD, "got {$sn->status}");

            // 1f. invalid: SOLD → AVAILABLE harus throw
            $threw = false;
            try {
                SerialNumber::transitionTo($sn, SerialNumber::STATUS_AVAILABLE);
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $check('SOLD→AVAILABLE throw', $threw, '(tidak throw)');

            // 1g. invalid: status tak dikenal harus throw
            $threw = false;
            try {
                SerialNumber::transitionTo($sn, 'NONEXISTENT');
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $check('status tak dikenal throw', $threw, '(tidak throw)');

            // 2. Relasi InternalRepair ↔ ReturnRequest (SN melekat, status SOLD dari langkah 1e)
            $rma = ReturnRequest::create([
                'rma_number'       => 'RMA-SELFCHECK-' . uniqid(),
                'status'           => ReturnRequest::STATUS_APPROVED,
                'warranty_type'    => 'store',
                'issue_type'       => ReturnRequest::ISSUE_OTHER,
                'issue_description'=> 'Self-check dummy.',
                'serial_number_id' => $sn->id,
                'product_id'       => $sn->product_id,
            ]);
            $rma->internalRepair()->create(['status' => 'pending']);
            $check('InternalRepair terhubung ke RMA', $rma->internalRepair->rma_id === $rma->id, "got " . $rma->internalRepair?->rma_id);

            // 3. Relasi VendorClaim ↔ ReturnRequest
            $rma->vendorClaim()->create([
                'status' => \App\Models\AfterSales\VendorClaim::STATUS_SENT,
            ]);
            $check('VendorClaim terhubung ke RMA', $rma->vendorClaim->rma_id === $rma->id, "got " . $rma->vendorClaim?->rma_id);

            $svc = app(ReturnWorkflowService::class);
            $threw = false;

            // 4. Guard: markRefundCompleted throw kalau status bukan REFUND_PENDING
            $threw = false;
            try {
                $svc->markRefundCompleted($rma);
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $check('guard: markRefundCompleted throw pada status salah', $threw, '(tidak throw)');

            // 5. VENDOR SEQUENCE: kirim → tolak klaim (unit tetap di vendor) → unit kembali → READY
            app(\App\Services\ReturnService::class)->sendToVendor($rma, now());
            $check('sendToVendor → SENT_TO_VENDOR', $rma->status === ReturnRequest::STATUS_SENT_TO_VENDOR, "got {$rma->status}");

            // 5a. TOLAK klaim BELUM berarti unit kembali: RMA harus tetap SENT_TO_VENDOR
            $svc->rejectVendorClaim($rma, 'Garansi tidak ditanggung supplier.');
            $check('rejectVendorClaim → klaim rejected', $rma->vendorClaim->status === \App\Models\AfterSales\VendorClaim::STATUS_REJECTED, "got {$rma->vendorClaim->status}");
            $check('rejectVendorClaim TIDAK pindahkan RMA (fisik di vendor)',
                $rma->status === ReturnRequest::STATUS_SENT_TO_VENDOR, "got {$rma->status}");
            $check('rejectVendorClaim TIDAK ubah SN status', $sn->status === SerialNumber::STATUS_SOLD, "got {$sn->status}");

            // 5b. ANTARA: returnRejectedUnit SEBELUM claim rejected harus throw
            $threw = false;
            $rma->vendorClaim->update(['status' => \App\Models\AfterSales\VendorClaim::STATUS_SENT]);
            try {
                $svc->returnRejectedUnit($rma);
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $rma->vendorClaim->update(['status' => \App\Models\AfterSales\VendorClaim::STATUS_REJECTED]);
            $check('guard: returnRejectedUnit throw bila klaim belum rejected', $threw, '(tidak throw)');

            // 5c. UNIT FISIK KEMBALI → READY_FOR_RETURN (baru di titik ini RMA pindah)
            $svc->returnRejectedUnit($rma, 'Unit diterima kembali, tanpa penyelesaian.');
            $check('returnRejectedUnit → klaim rejected → READY_FOR_RETURN',
                $rma->status === ReturnRequest::STATUS_READY_FOR_RETURN, "got {$rma->status}");

            // 6. WARRANTY_REJECTED non-terminal (kasus store/inspeksi): → READY_FOR_RETURN
            $rma->update([
                'status'           => ReturnRequest::STATUS_WARRANTY_REJECTED,
                'warranty_decision'=> ReturnRequest::WARRANTY_REJECTED,
            ]);
            $svc->confirmWarrantyRejected($rma);
            $check('WARRANTY_REJECTED → READY_FOR_RETURN (non-terminal)',
                $rma->status === ReturnRequest::STATUS_READY_FOR_RETURN, "got {$rma->status}");

            // 6b. Guard: confirmWarrantyRejected throw kalau status bukan WARRANTY_REJECTED
            $threw = false;
            try {
                $svc->confirmWarrantyRejected($rma);
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $check('guard: confirmWarrantyRejected throw pada status salah', $threw, '(tidak throw)');

            // 7. SN post-state via transitionTo: RETURNED → SOLD valid (state-machine-guarded)
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_RETURNED);
            SerialNumber::transitionTo($sn, SerialNumber::STATUS_SOLD);
            $check('SN kembali ke klien via transitionTo (RETURNED→SOLD)',
                $sn->status === SerialNumber::STATUS_SOLD, "got {$sn->status}");

            // 8. NO_FAULT_FOUND revisit — pakai RMA kedua agar alur no-fault utuh
            $rma2 = ReturnRequest::create([
                'rma_number'        => 'RMA-SELFCHECK-2-' . uniqid(),
                'status'            => ReturnRequest::STATUS_RECEIVED,
                'warranty_type'     => 'store',
                'issue_type'        => ReturnRequest::ISSUE_OTHER,
                'issue_description' => 'Self-check no-fault.',
                'serial_number_id'  => $sn->id,
                'product_id'        => $sn->product_id,
                'warranty_decision' => ReturnRequest::WARRANTY_NA,
                'inspection_result' => ReturnRequest::INSPECTION_NO_FAULT_FOUND,
                'resolution_type'   => ReturnRequest::RESOLUTION_NO_FAULT_FOUND,
            ]);
            $svc->resolveNoFaultFound($rma2);
            $check('no_fault_found → READY_FOR_RETURN', $rma2->status === ReturnRequest::STATUS_READY_FOR_RETURN, "got {$rma2->status}");

            // 8b. Guard: resolveNoFaultFound throw bila warranty belum approved/na
            $threw = false;
            $rma2->update(['status' => ReturnRequest::STATUS_RECEIVED, 'warranty_decision' => ReturnRequest::WARRANTY_PENDING]);
            try {
                $svc->resolveNoFaultFound($rma2);
            } catch (\InvalidArgumentException $e) {
                $threw = true;
            }
            $check('guard: no_fault throw bila warranty belum approved/na', $threw, '(tidak throw)');

            throw new \Exception('rollback');
        } catch (\Throwable $e) {
            if ($e->getMessage() !== 'rollback') {
                $this->error('  \u{2717} exception: ' . $e->getMessage());
                $fail++;
            }
        } finally {
            DB::rollBack();
        }

        $this->newLine();
        if ($fail === 0) {
            $this->info("PASS ({$pass} checks)");
            return self::SUCCESS;
        }

        $this->error("FAIL ({$fail} gagal, {$pass} lolos)");
        return self::FAILURE;
    }
}