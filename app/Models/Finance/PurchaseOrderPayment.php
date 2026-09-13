<?php

namespace App\Models\Finance;

use App\Models\Procurement\PurchaseInvoice;
use App\Models\Procurement\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderPayment extends Model
{
    protected $table = 'nx_purchase_order_payments';
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date'];

    // Pembayaran terikat ke Tagihan (Purchase Invoice) yang dibayar.
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    // PO sumber utang (di mana tagihan dibuat dari PO).
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    // Sentralisasi Logika Keuangan
    protected static function booted()
    {
        static::created(function ($payment) {
            $invoice = $payment->purchaseInvoice;

            // created_by column di nx_financial_records ber-FK ke nx_employees,
            // jadi simpan employee id (mirip Payment::syncFinancialRecord),
            // bukan auth()->id() (user id) — kalau pakai user id, FK constraint
            // akan gagal saat user tidak punya Employee terkait.
            $employeeId = $payment->creator?->employee?->id
                ?? auth()->user()?->employee?->id
                ?? null;

            // IDEMPOTENT: satu payment → tepat satu FinancialRecord.
            // Identity = PAYMENT id (unik per pembayaran), BUKAN invoice id.
            // updateOrCreate membuat konflik partial payment / double-click
            // menjadi no-op, bukan dupicasi jurnal.
            FinancialRecord::updateOrCreate(
                [
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                ],
                [
                    'transaction_date'   => $payment->payment_date,
                    'type'               => 'pengeluaran',
                    'amount'             => $payment->amount,
                    'category'           => 'Purchase Invoice Payment',
                    'account_type'       => 'cogs',
                    'cash_flow_activity' => 'operating',
                    'normal_balance'     => 'debit',
                    'description'        => 'Pembayaran Tagihan ke Supplier: ' . ($invoice?->supplier?->name ?? '-') . ' via ' . strtoupper($payment->payment_method),
                    'reference_number'   => $payment->payment_number, // HANYA label, bukan identity (lihat audit Phase 2)
                    'reference_type'     => PurchaseOrderPayment::class,
                    'reference_id'       => $payment->id,
                    'created_by'         => $employeeId,
                ]
            );

            // Trigger update status di Purchase Invoice (Otomatis hitung sisa tagihan)
            if ($invoice) {
                $invoice->recalculateStatus();
            }
        });

        static::deleted(function ($payment) {
            // Hapus HANYA jurnal milik payment ini — jangan sentuh payment lain
            // pada invoice yang sama.
            FinancialRecord::query()
                ->where('reference_type', PurchaseOrderPayment::class)
                ->where('reference_id', $payment->id)
                ->delete();

            if ($payment->purchaseInvoice) {
                $payment->purchaseInvoice->recalculateStatus();
            }
        });
    }
}