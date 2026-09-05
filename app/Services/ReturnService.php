<?php

namespace App\Services;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use App\Models\Procurement\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnService
{
    /**
     * Process a supplier warranty return that has been received back from vendor.
     *
     * Resolution types from supplier decision:
     *  - repaired  : SN stays (repaired), goes back to customer
     *  - replaced  : new SN shipped to customer, old SN RETURNED
     *  - refund    : supplier refunds cash/PR; customer gets credit/refund (via processRefund)
     *  - rejected  : SN rejected, revert to SOLD (customer keeps item, warranty denied)
     *
     * Creates PurchaseReturn (for repaired/replaced/refund) + StockTransaction, updates SN status.
     */
    public function receiveFromVendor(ReturnRequest $returnRequest, array $data): ?PurchaseReturn
    {
        return DB::transaction(function () use ($returnRequest, $data) {
            /** @var SerialNumber $serial */
            $serial = $returnRequest->serialNumber;
            $supplier = $serial->supplier;
            $resolution = $data['resolution_type'] ?? ReturnRequest::RESOLUTION_REPAIR_AND_RETURN;

            $purchaseReturn = null;
            $snUnitPrice = 0;

            // ---- Create PurchaseReturn only for repair / replacement / refund ----
            if (in_array($resolution, [
                ReturnRequest::RESOLUTION_REPAIR_AND_RETURN,
                ReturnRequest::RESOLUTION_REPLACEMENT,
                ReturnRequest::RESOLUTION_REFUND,
            ], true)) {
                // Use SN's purchase order item unit_price if available, else 0
                $snUnitPrice = $this->resolveSnUnitPrice($serial);

                $purchaseReturn = PurchaseReturn::create([
                    'return_number'    => PurchaseReturn::generateReturnNumber(),
                    'supplier_id'      => $supplier?->id,
                    'goods_receipt_id' => $returnRequest->purchaseReturn?->goods_receipt_id,
                    'return_date'      => now()->toDateString(),
                    'status'           => 'approved',
                    'resolution_type'  => $resolution === 'refund' ? 'refund' : 'credit_note',
                    'notes'            => $data['vendor_notes'] ?? null,
                    'created_by'       => auth()->id(),
                ]);

                $purchaseReturn->items()->create([
                    'product_id'        => $serial->product_id,
                    'serial_number_id'  => $serial->id,
                    'quantity'          => 1,
                    'unit_price'        => $snUnitPrice,
                    'reason'            => "Supplier warranty claim — RMA {$returnRequest->rma_number}",
                ]);
            }

            // ---- Handle SN status + stock transaction per resolution ----
            if ($resolution === ReturnRequest::RESOLUTION_REPAIR_AND_RETURN) {
                // SN back from vendor, repaired — goes to RETURNED (ready for customer pickup)
                $serial->update(['status' => SerialNumber::STATUS_RETURNED]);

                StockTransaction::create([
                    'product_id'        => $serial->product_id,
                    'warehouse_id'      => $serial->warehouse_id,
                    'serial_number_id'  => $serial->id,
                    'transaction_code'  => 'RMA-SUP-' . $returnRequest->rma_number,
                    'mutation_type'     => 'stock_in',
                    'transaction_date'  => now(),
                    'quantity'          => 1,
                    'reference_id'      => $returnRequest->id,
                    'reference_type'    => ReturnRequest::class,
                    'notes'             => 'Return from vendor after warranty repair',
                ]);
            } elseif ($resolution === ReturnRequest::RESOLUTION_REPLACEMENT) {
                // New SN shipped to customer; old SN → RETURNED
                $serial->update(['status' => SerialNumber::STATUS_RETURNED]);

                StockTransaction::create([
                    'product_id'        => $serial->product_id,
                    'warehouse_id'      => $serial->warehouse_id,
                    'serial_number_id'  => $serial->id,
                    'transaction_code'  => 'RMA-SUP-' . $returnRequest->rma_number,
                    'mutation_type'     => 'stock_in',
                    'transaction_date'  => now(),
                    'quantity'          => 1,
                    'reference_id'      => $returnRequest->id,
                    'reference_type'    => ReturnRequest::class,
                    'notes'             => 'Return from vendor after warranty claim',
                ]);

                if (! empty($data['new_serial_number'])) {
                    $newSerial = SerialNumber::where('serial_number', $data['new_serial_number'])->first();
                    if ($newSerial) {
                        $data['new_serial_number_id'] = $newSerial->id;
                        $newSerial->update([
                            'status'     => SerialNumber::STATUS_SOLD,
                            'customer_id' => $serial->customer_id,
                        ]);

                        StockTransaction::create([
                            'product_id'        => $newSerial->product_id,
                            'warehouse_id'      => $serial->warehouse_id,
                            'serial_number_id'  => $newSerial->id,
                            'transaction_code'  => 'RMA-REPLACE-' . $returnRequest->rma_number,
                            'mutation_type'     => 'delivery',
                            'transaction_date'  => now(),
                            'quantity'          => 1,
                            'reference_id'      => $returnRequest->id,
                            'reference_type'    => ReturnRequest::class,
                            'notes'             => 'Replacement unit shipped to customer',
                        ]);
                    }
                }
            } elseif ($resolution === ReturnRequest::RESOLUTION_REFUND) {
                // Supplier refunds cash — SN stays with customer; create stock transaction audit only
                StockTransaction::create([
                    'product_id'        => $serial->product_id,
                    'warehouse_id'      => $serial->warehouse_id,
                    'serial_number_id'  => $serial->id,
                    'transaction_code'  => 'RMA-SUP-REFUND-' . $returnRequest->rma_number,
                    'mutation_type'     => 'adjustment',
                    'transaction_date'  => now(),
                    'quantity'          => 0,
                    'reference_id'      => $returnRequest->id,
                    'reference_type'    => ReturnRequest::class,
                    'notes'             => 'Supplier warranty refund processed — SN retained by customer',
                ]);

                // Process customer refund (financial + AR)
                $this->processRefund($returnRequest, $snUnitPrice);
            } elseif ($data['warranty_decision'] === ReturnRequest::WARRANTY_REJECTED) {
                // Warranty denied — revert SN to SOLD (customer keeps item)
                if (in_array($serial->status, [SerialNumber::STATUS_DEFECTIVE, SerialNumber::STATUS_RETURNED], true)) {
                    $serial->update(['status' => SerialNumber::STATUS_SOLD]);
                }

                StockTransaction::create([
                    'product_id'        => $serial->product_id,
                    'warehouse_id'      => $serial->warehouse_id,
                    'serial_number_id'  => $serial->id,
                    'transaction_code'  => 'RMA-REJECT-' . $returnRequest->rma_number,
                    'mutation_type'     => 'adjustment',
                    'transaction_date'  => now(),
                    'quantity'          => 0,
                    'reference_id'      => $returnRequest->id,
                    'reference_type'    => ReturnRequest::class,
                    'notes'             => 'Warranty rejected by supplier — SN remains customer possession',
                ]);
            }

            // ---- Link back to RMA + set final status ----
            $returnRequest->update([
                'purchase_return_id'    => $purchaseReturn?->id,
                'resolution_type'       => $resolution,
                'new_serial_number_id'  => $data['new_serial_number_id'] ?? null,
                'vendor_notes'          => $data['vendor_notes'] ?? null,
                'back_from_vendor_date' => now()->toDateString(),
            ]);

            // Warranty rejected items don't go to "ready for return" — closed as warranty_rejected
            if (($data['warranty_decision'] ?? null) === ReturnRequest::WARRANTY_REJECTED) {
                $returnRequest->update(['status' => ReturnRequest::STATUS_WARRANTY_REJECTED]);
            } else {
                $returnRequest->update(['status' => ReturnRequest::STATUS_READY_FOR_RETURN]);
            }

            // Create financial record: AP reduction / credit note to supplier
            if ($purchaseReturn) {
                \App\Models\Finance\FinancialRecord::create([
                    'transaction_date' => now(),
                    'description'      => "Credit note untuk retur RMA {$returnRequest->rma_number} ke supplier {$supplier?->name}",
                    'type'             => 'hutang',
                    'amount'           => $snUnitPrice,
                    'category'         => 'Accounts Payable',
                    'reference_id'     => $returnRequest->id,
                    'reference_type'   => ReturnRequest::class,
                    'reimburse_id'     => null,
                ]);
            }

            return $purchaseReturn;
        });
    }

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
     * Process customer refund after supplier issued refund.
     *
     * Creates a single FinancialRecord: piutang (potong AR customer / credit note).
     * AR potong berarti tagihan customer berkurang. Untuk cash refund (kas keluar),
     * buat FinancialRecord 'pemasukan' via finance module manual trigger.
     */
    public function processRefund(ReturnRequest $returnRequest, float $unitPrice = 0): void
    {
        DB::transaction(function () use ($returnRequest, $unitPrice) {
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
                $serial->update(['status' => SerialNumber::STATUS_SOLD]);

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
     * Send RMA to supplier — creates a Purchase Order claim against the supplier.
     *
     * PO is created so procurement can track the warranty claim + supplier billing.
     * Link PO back to RMA via procurement_claim_id.
     */
    public function sendToVendor(ReturnRequest $returnRequest, $sentDate = null): void
    {
        DB::transaction(function () use ($returnRequest, $sentDate) {
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
                'status'            => ReturnRequest::STATUS_SENT_TO_VENDOR,
                'sent_to_vendor_date' => $sentDate,
            ]);
        });
    }

    }
