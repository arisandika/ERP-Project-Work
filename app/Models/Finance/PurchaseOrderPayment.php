<?php

namespace App\Models\Finance;

use App\Models\Procurement\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPayment extends Model
{
    protected $table = 'nx_purchase_order_payments';
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    // Sentralisasi Logika Keuangan
    protected static function booted()
    {
        static::created(function ($payment) {
            // Otomatis potong kas/uang keluar saat pembayaran PO disimpan
            FinancialRecord::create([
                'transaction_date' => $payment->payment_date,
                'type'             => 'pengeluaran',
                'amount'           => $payment->amount,
                'category'         => 'Purchase Order',
                'description'      => 'Pembayaran PO ke Supplier: ' . ($payment->purchaseOrder->supplier->name ?? '-') . ' via ' . strtoupper($payment->payment_method),
                'reference_number' => $payment->payment_number,
                'reference_type'   => PurchaseOrder::class,
                'reference_id'     => $payment->purchase_order_id,
                'created_by'       => auth()->id() ?? 1,
            ]);
        });

        static::deleted(function ($payment) {
            // Hapus catatan keuangan terkait jika pembayaran PO dihapus
            FinancialRecord::where('reference_type', PurchaseOrder::class)
                ->where('reference_id', $payment->purchase_order_id)
                ->where('reference_number', $payment->payment_number)
                ->delete();
        });
    }
}
