<?php

namespace App\Console\Commands;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
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

            // 2. Relasi InternalRepair ↔ ReturnRequest
            $rma = ReturnRequest::create([
                'rma_number'       => 'RMA-SELFCHECK-' . uniqid(),
                'status'           => ReturnRequest::STATUS_APPROVED,
                'warranty_type'    => 'store',
                'issue_type'       => ReturnRequest::ISSUE_OTHER,
                'issue_description'=> 'Self-check dummy.',
            ]);
            $rma->internalRepair()->create(['status' => 'pending']);
            $check('InternalRepair terhubung ke RMA', $rma->internalRepair->rma_id === $rma->id, "got " . $rma->internalRepair?->rma_id);

            // 3. Relasi VendorClaim ↔ ReturnRequest
            $rma->vendorClaim()->create([]);
            $check('VendorClaim terhubung ke RMA', $rma->vendorClaim->rma_id === $rma->id, "got " . $rma->vendorClaim?->rma_id);

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