<?php

namespace App\Services;

use App\Models\AfterSales\ReturnRequest;
use App\Models\AfterSales\VendorClaim;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnService
{
    /**
     * Resolve unit price paid to supplier for this SN, from PO items.
     */
    protected function resolveSnUnitPrice(SerialNumber $serial): float
    {
        $poItem = $serial->purchaseOrder?->items()
            ->where('item_id', $serial->product_id)
            ->orWhere('product_id', $serial->product_id)
            ->first();

        return (float) ($poItem->unit_price ?? 0);
    }

    /**
     * Process customer refund resolution.
     *
     * Creates a FinancialRecord piutang (credit note / pengurang piutang customer)
     * dan menandai refund_status=pending — refund BELUM selesai sampai Finance
     * mengonfirmasi (lihat markRefundCompleted di ReturnWorkflowService).
     *
     * @return float jumlah refund yang dicatat
     */
    public function processRefund(ReturnRequest $returnRequest, float $unitPrice = 0): float
    {
        return DB::transaction(function () use ($returnRequest, $unitPrice) {
            $qty = (int) ($returnRequest->qty ?? 1);
            $unitPrice = $unitPrice > 0 ? $unitPrice : ($returnRequest->invoiceItem?->unit_price ?? 0);
            $refundAmount = round($qty * $unitPrice, 2);

            \App\Models\Finance\FinancialRecord::create([
                'transaction_date' => now(),
                'description'      => "Credit note / refund RMA {$returnRequest->rma_number} ke customer {$returnRequest->customer?->name}",
                'type'             => 'piutang',
                'amount'           => $refundAmount,
                'category'         => 'Accounts Receivable',
                'reference_id'     => $returnRequest->id,
                'reference_type'   => ReturnRequest::class,
                'reimburse_id'     => null,
            ]);

            $returnRequest->update([
                'refund_status'  => ReturnRequest::REFUND_PENDING,
                'resolution_type'=> ReturnRequest::RESOLUTION_REFUND,
            ]);

            return $refundAmount;
        });
    }

    /**
     * Reject an RMA — revert SN status back to SOLD (returned to customer possession).
     * Applicable when warranty claim is denied by vendor or store.
     */
    public function reject(ReturnRequest $returnRequest, ?string $reason = null): void
    {
        DB::transaction(function () use ($returnRequest, $reason) {
            /** @var SerialNumber $serial */
            $serial = $returnRequest->serialNumber;

            // Update RMA: mark rejected + store reason
            $newNotes = $reason
                ? "\n\n[REJECT] " . $reason
                : '';
            $returnRequest->update([
                'status'         => ReturnRequest::STATUS_REJECTED,
                'internal_notes' => ($returnRequest->internal_notes ?? '') . $newNotes,
            ]);

            // Revert SN: rejected SN goes back to SOLD (still customer possession)
            if ($serial && in_array($serial->status, [
                SerialNumber::STATUS_DEFECTIVE,
                SerialNumber::STATUS_RETURNED,
            ], true)) {
                SerialNumber::transitionTo($serial, SerialNumber::STATUS_SOLD);

                StockTransaction::create([
                    'product_id'        => $serial->product_id,
                    'warehouse_id'      => $serial->warehouse_id,
                    'serial_number_id'  => $serial->id,
                    'transaction_code'  => 'RMA-REJECT-' . $returnRequest->rma_number,
                    'mutation_type'     => 'adjustment',
                    'transaction_date'  => now(),
                    'quantity'          => 1,
                    'reference_id'      => $returnRequest->id,
                    'reference_type'    => ReturnRequest::class,
                    'notes'             => 'RMA rejected — SN reverted to SOLD (customer possession)',
                ]);
            }
        });
    }

    /**
     * Send RMA to supplier — creates a Purchase Order claim against the supplier
     * + entri VendorClaim (lifecycle klaim vendor terpisah dari status RMA).
     *
     * PO is created so procurement can track the warranty claim + supplier billing.
     * Link PO back to RMA via procurement_claim_id.
     */
    public function sendToVendor(ReturnRequest $returnRequest, $sentDate = null, ?string $vendorNotes = null): void
    {
        DB::transaction(function () use ($returnRequest, $sentDate, $vendorNotes) {
            /** @var SerialNumber|null $serial */
            $serial = $returnRequest->serialNumber;
            $supplier = $serial?->supplier;
            $sentDate = $sentDate ? \Illuminate\Support\Carbon::parse($sentDate) : now();

            // Only create PO if supplier exists and no prior PO claim
            if ($supplier && ! $returnRequest->procurementClaim) {
                $unitPrice = $this->resolveSnUnitPrice($serial);

                $po = \App\Models\Procurement\PurchaseOrder::create([
                    'supplier_id'    => $supplier->id,
                    'title'          => "Warranty Claim RMA {$returnRequest->rma_number}",
                    'order_date'     => now()->toDateString(),
                    'status'         => \App\Enums\Procurement\PurchaseOrderStatus::SENT,
                    'grand_total'    => $unitPrice, // single item, qty 1
                    'notes'          => "Warranty claim for RMA {$returnRequest->rma_number}. "
                        . "Issue: " . Str::limit($returnRequest->issue_description ?? '', 200),
                    'created_by'     => auth()->id(),
                ]);

                $po->items()->create([
                    'product_id'   => $serial->product_id,
                    'quantity'     => 1,
                    'unit_price'   => $unitPrice,
                    'total_price'  => $unitPrice,
                ]);

                // Link PO back to RMA
                $returnRequest->update(['procurement_claim_id' => $po->id]);
            }

            // Update RMA status + sent date
            $returnRequest->update([
                'status'              => ReturnRequest::STATUS_SENT_TO_VENDOR,
                'sent_to_vendor_date' => $sentDate,
            ]);

            // Catat klaim vendor terpisah (hanya jika belum ada untuk RMA ini)
            if (! $returnRequest->vendorClaim()->exists()) {
                $returnRequest->vendorClaim()->create([
                    'supplier_id'      => $supplier?->id,
                    'purchase_order_id'=> $returnRequest->procurement_claim_id,
                    'status'           => VendorClaim::STATUS_SENT,
                    'resolution_type'  => $returnRequest->resolution_type,
                    'vendor_notes'     => $vendorNotes,
                    'sent_at'          => now(),
                    'created_by'       => auth()->id(),
                ]);
            }
        });
    }
}