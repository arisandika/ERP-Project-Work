<?php

namespace App\Console\Commands;

use App\Models\Finance\FinancialRecord;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\GoodsReceiptItem;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Procurement\PurchaseReturn;
use App\Models\Procurement\PurchaseReturnItem;
use App\Models\Procurement\Supplier;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Self-check PurchaseReturn flow (draft → approved → shipped → completed) +
 * anti double-posting + quantity validation. Dijalankan tanpa RefreshDatabase
 * (amankan schema prod): semua create dibungkus transaction, di-rollback di akhir.
 *
 *   php artisan purchase-return:selfcheck
 */
class PurchaseReturnSelfCheckCommand extends Command
{
    protected $signature = 'purchase-return:selfcheck';

    protected $description = 'Self-check refinement PurchaseReturn (workflow + double-posting guard + qty)';

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

        $this->info('PurchaseReturn self-check');

        DB::beginTransaction();
        try {
            $user = User::query()->first() ?? User::factory()->create();
            auth()->login($user);

            $uniq    = substr(md5(uniqid('', true)), 0, 8);
            $category = Category::create(['name' => 'SC ' . $uniq, 'description' => '']);
            $unit     = Unit::create(['name' => 'SC ' . $uniq, 'symbol' => 'pcs']);
            $product  = Product::create([
                'product_name'   => 'SelfCheck PR ' . uniqid(),
                'category_id'    => $category->id,
                'unit_id'        => $unit->id,
                'min_stock'      => 1,
                'selling_price'  => 5000,
                'is_serialized'  => 0,
            ]);
            $warehouse = Warehouse::query()->first() ?? Warehouse::create(['warehouse_name' => 'SC', 'location' => 'SC']);
            $supplier  = Supplier::create(['name' => 'SelfCheck Supplier ' . uniqid(), 'supplier_code' => 'SC-' . uniqid(), 'category' => 'company']);

            $po = PurchaseOrder::create([
                'po_number'        => 'PO-SC-' . uniqid(),
                'supplier_id'      => $supplier->id,
                'title'            => 'SelfCheck PO',
                'order_date'       => now()->toDateString(),
                'status'           => \App\Enums\Procurement\PurchaseOrderStatus::SENT,
                'created_by'       => $user->id,
            ]);
            $poItem = PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id'        => $product->id,
                'quantity'          => 10,
                'quantity_received' => 10,
                'unit_price'        => 4000,
                'total_price'       => 40000,
            ]);
            $gr = GoodsReceipt::create([
                'gr_number'          => 'GR-SC-' . uniqid(),
                'title'              => 'SelfCheck GR',
                'purchase_order_id'  => $po->id,
                'supplier_id'        => $supplier->id,
                'warehouse_id'       => $warehouse->id,
                'receipt_date'       => now()->toDateString(),
                'status'             => 'completed',
                'received_by'        => $user->id,
            ]);
            $grItem = GoodsReceiptItem::create([
                'goods_receipt_id'       => $gr->id,
                'purchase_order_item_id' => $poItem->id,
                'product_id'             => $product->id,
                'quantity_received'      => 10,
            ]);

            $mkReturn = function (int $qty, ?int $grItemId = null) use ($user, $supplier, $po, $gr, $product) {
                $pr = PurchaseReturn::create([
                    'return_number'      => 'PRT-SC-' . uniqid(),
                    'supplier_id'        => $supplier->id,
                    'purchase_order_id'  => $po->id,
                    'goods_receipt_id'   => $gr->id,
                    'return_date'        => now()->toDateString(),
                    'status'             => PurchaseReturn::STATUS_DRAFT,
                    'resolution_type'    => 'credit_note',
                    'notes'              => 'self-check',
                    'created_by'         => $user->id,
                ]);
                PurchaseReturnItem::create([
                    'purchase_return_id'   => $pr->id,
                    'product_id'           => $product->id,
                    'goods_receipt_item_id'=> $grItemId,
                    'quantity'             => $qty,
                    'unit_price'           => 4000,
                    'reason'               => 'self-check',
                ]);
                return $pr->fresh();
            };

            $wfSvc  = app(\App\Services\Procurement\PurchaseReturnWorkflowService::class);

            // Setup: user berizin (semua 3 permission) + user tak berizin (tanpa permission).
            $user->givePermissionTo([
                'approve_procurement::purchase::return',
                'ship_procurement::purchase::return',
                'complete_procurement::purchase::return',
            ]);
            $unauthorized = User::factory()->create();
            auth()->login($user);

            // ---- 1. WORKFLOW LENGKAP via service (valid) ----
            $pr = $mkReturn(3, $grItem->id);
            $wfSvc->approve($pr->fresh());
            $check('valid: draft → approved (service)', $pr->fresh()->status === PurchaseReturn::STATUS_APPROVED, "got {$pr->fresh()->status}");

            $wfSvc->ship($pr->fresh());
            $check('valid: approved → shipped (service)', $pr->fresh()->status === PurchaseReturn::STATUS_SHIPPED, "got {$pr->fresh()->status}");

            $wfSvc->complete($pr->fresh());
            $check('valid: shipped → completed (service)', $pr->fresh()->status === PurchaseReturn::STATUS_COMPLETED, "got {$pr->fresh()->status}");
            $frCount = FinancialRecord::where('reference_type', PurchaseReturn::class)->where('reference_id', $pr->id)->count();
            $check('FinancialRecord dibuat sekali', $frCount === 1, "got {$frCount}");

            // Double-posting via service juga ditolak (status completed)
            $threw = false;
            try {
                $wfSvc->complete($pr->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'Transisi tidak valid') || str_contains($e->getMessage(), 'sudah diselesaikan');
            }
            $check('double-posting throw (sudah completed)', $threw, '(tidak throw)');
            $check('double-posting tidak tambah FinancialRecord', FinancialRecord::where('reference_type', PurchaseReturn::class)->where('reference_id', $pr->id)->count() === 1, 'ada record');

            // ---- 2. TRANSISI LOMBAT DITOLAK ----
            $pr = $mkReturn(3, $grItem->id);
            $threw = false;
            try {
                $wfSvc->ship($pr->fresh()); // draft → shipped (lompat)
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'Transisi tidak valid');
            }
            $check('draft → shipped DITOLAK', $threw, '(tidak throw)');
            $check('draft → shipped: status tetap draft', $pr->fresh()->status === PurchaseReturn::STATUS_DRAFT, "got {$pr->fresh()->status}");

            $pr2 = $mkReturn(3, $grItem->id);
            $wfSvc->approve($pr2->fresh());
            $threw = false;
            try {
                $wfSvc->complete($pr2->fresh()); // approved → completed (lompat, lewati ship)
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'Transisi tidak valid');
            }
            $check('approved → completed DITOLAK', $threw, '(tidak throw)');
            $check('approved → completed: status tetap approved', $pr2->fresh()->status === PurchaseReturn::STATUS_APPROVED, "got {$pr2->fresh()->status}");

            // completed snap: ship/complete/approve semua ditolak
            $pr3 = $mkReturn(3, $grItem->id);
            $wfSvc->approve($pr3->fresh());
            $wfSvc->ship($pr3->fresh());
            $wfSvc->complete($pr3->fresh());
            $threw = false;
            try {
                $wfSvc->ship($pr3->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'Transisi tidak valid');
            }
            $check('completed → ship DITOLAK', $threw, '(tidak throw)');
            $threw = false;
            try {
                $wfSvc->complete($pr3->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'Transisi tidak valid') || str_contains($e->getMessage(), 'sudah diselesaikan');
            }
            $check('completed → complete DITOLAK', $threw, '(tidak throw)');

            // ---- 3. UNAUTHORIZED DITOLAK ----
            $prAuth = $mkReturn(3, $grItem->id); // draft
            auth()->login($unauthorized);

            $threw = false;
            try {
                $wfSvc->approve($prAuth->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'tidak memiliki izin');
            }
            $check('unauthorized: approve DITOLAK', $threw, '(tidak throw)');

            $prAuth2 = $mkReturn(3, $grItem->id);
            auth()->login($user);
            $wfSvc->approve($prAuth2->fresh());
            auth()->login($unauthorized);
            $threw = false;
            try {
                $wfSvc->ship($prAuth2->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'tidak memiliki izin');
            }
            $check('unauthorized: ship DITOLAK', $threw, '(tidak throw)');

            $prAuth3 = $mkReturn(3, $grItem->id);
            auth()->login($user);
            $wfSvc->approve($prAuth3->fresh());
            $wfSvc->ship($prAuth3->fresh());
            auth()->login($unauthorized);
            $threw = false;
            try {
                $wfSvc->complete($prAuth3->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'tidak memiliki izin');
            }
            $check('unauthorized: complete DITOLAK', $threw, '(tidak throw)');
            $check('unauthorized: status tidak berubah', $prAuth3->fresh()->status === PurchaseReturn::STATUS_SHIPPED, "got {$prAuth3->fresh()->status}");

            // ---- 4. QTY > GR validasi (service finance) ----
            auth()->login($user);
            $prQty = $mkReturn(15, $grItem->id);
            $wfSvc->approve($prQty->fresh());
            $wfSvc->ship($prQty->fresh());
            $threw = false;
            try {
                $wfSvc->complete($prQty->fresh());
            } catch (\Throwable $e) {
                $threw = str_contains($e->getMessage(), 'melebihi quantity diterima');
            }
            $check('qty > GR diterima throw', $threw, '(tidak throw)');
            $check('qty > GR: status tetap shipped', $prQty->fresh()->status === PurchaseReturn::STATUS_SHIPPED, "got {$prQty->fresh()->status}");
            $check('qty > GR: tidak ada FinancialRecord', FinancialRecord::where('reference_type', PurchaseReturn::class)->where('reference_id', $prQty->id)->count() === 0, 'ada record');

            // ---- 5. TANPA GR LINKAGE ----
            $prNoGr = $mkReturn(5, null);
            $wfSvc->approve($prNoGr->fresh());
            $wfSvc->ship($prNoGr->fresh());
            $wfSvc->complete($prNoGr->fresh());
            $check('tanpa GR linkage tetap completed', $prNoGr->fresh()->status === PurchaseReturn::STATUS_COMPLETED, "got {$prNoGr->fresh()->status}");
            $check('tanpa GR: FinancialRecord dibuat', FinancialRecord::where('reference_type', PurchaseReturn::class)->where('reference_id', $prNoGr->id)->count() === 1, 'tidak ada');

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