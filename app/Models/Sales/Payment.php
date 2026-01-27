<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'nx_payments';
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'nx_invoice_id');
    }

    // Auto update status invoice setiap kali ada pembayaran
    protected static function booted()
    {
        static::saved(function ($payment) {
            $payment->invoice->recalculateStatus();
        });

        static::deleted(function ($payment) {
            $payment->invoice->recalculateStatus();
        });
    }
}

