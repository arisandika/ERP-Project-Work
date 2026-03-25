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

    // FUNGSI INI KITA PINDAH DARI EditInvoice.php KE SINI
    public static function generatePaymentNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'PAY';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = self::where('payment_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('payment_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }

    protected static function booted()
    {
        // EVENT BARU: Isi nomor otomatis sebelum save ke database
        static::creating(function ($payment) {
            if (empty($payment->payment_number) || $payment->payment_number === 'AUTO-GENERATED') {
                $payment->payment_number = self::generatePaymentNumber();
            }
        });

        static::saved(function ($payment) {
            if ($payment->invoice) {
                $payment->invoice->recalculateStatus();
            }
        });

        static::deleted(function ($payment) {
            if ($payment->invoice) {
                $payment->invoice->recalculateStatus();
            }
        });
    }
}
