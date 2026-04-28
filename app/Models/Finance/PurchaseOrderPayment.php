<?php

namespace App\Models\Finance;

use App\Models\Procurement\PurchaseInvoice; // Ubah import ini
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPayment extends Model
{
    protected $table = 'nx_purchase_order_payments';
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date'];

    // UBAH RELASI: Sekarang pembayaran terikat ke Tagihan (Purchase Invoice), bukan PO lagi
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    // Sentralisasi Logika Keuangan
    protected static function booted()
    {
        static::created(function ($payment) {
            $invoice = $payment->purchaseInvoice;

            // Otomatis potong kas/uang keluar saat pembayaran Tagihan disimpan
            FinancialRecord::create([
                'transaction_date'     => $payment->payment_date,
                'type'                 => 'pengeluaran',
                'amount'               => $payment->amount,
                'category'             => 'Purchase Invoice Payment',
                'account_type'         => 'cogs',
                'cash_flow_activity'   => 'operating',
                'normal_balance'       => 'debit',
                'description'          => 'Pembayaran Tagihan ke Supplier: ' . ($invoice->supplier->name ?? '-') . ' via ' . strtoupper($payment->payment_method),
                'reference_number'     => $payment->payment_number,
                'reference_type'       => PurchaseInvoice::class,
                'reference_id'         => $payment->purchase_invoice_id,
                'created_by'           => auth()->id() ?? 1,
            ]);

            // Trigger update status di Purchase Invoice (Otomatis hitung sisa tagihan)
            if ($invoice) {
                $invoice->recalculateStatus();
            }
        });

        static::deleted(function ($payment) {
            // Hapus catatan keuangan terkait jika pembayaran dihapus
            FinancialRecord::where('reference_type', PurchaseInvoice::class)
                ->where('reference_id', $payment->purchase_invoice_id)
                ->where('reference_number', $payment->payment_number)
                ->delete();

            // Kembalikan status tagihan
            if ($payment->purchaseInvoice) {
                $payment->purchaseInvoice->recalculateStatus();
            }
        });
    }
}
