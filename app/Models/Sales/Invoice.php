<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'nx_invoices';

    protected $fillable = [
        'nx_sales_order_id',
        'nx_customer_id',
        'nx_employee_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'grand_total'  => 'decimal:2',
    ];

    // --- RELATIONS ---

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'nx_sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id')->withDefault();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nx_employee_id')->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'nx_invoice_id');
    }

    /**
     * Relasi ke tabel Payments (History Pembayaran)
     * Pastikan model Payment sudah dibuat ya.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'nx_invoice_id');
    }

    // --- ACCESSORS / HELPERS ---

    /**
     * Hitung total uang yang sudah masuk (Sum dari tabel payments)
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Hitung sisa tagihan (Grand Total - Total Paid)
     */
    public function getRemainingBalanceAttribute(): float
    {
        // Pastikan tidak minus (floating point issue protection)
        $balance = (float) $this->grand_total - $this->total_paid;
        return $balance > 0 ? $balance : 0;
    }

    // --- BUSINESS LOGIC ---

    /**
     * Hitung ulang status invoice berdasarkan pembayaran.
     * Dipanggil otomatis dari Model Payment saat ada pembayaran baru/hapus.
     */
    public function recalculateStatus(): void
    {
        $totalPaid  = $this->total_paid;
        $grandTotal = (float) $this->grand_total;

        // Toleransi selisih koma (float precision)
        $tolerance = 0.01;

        if ($totalPaid >= ($grandTotal - $tolerance)) {
            $status = 'paid'; // Lunas
        } elseif ($totalPaid > 0) {
            $status = 'partial'; // Bayar Sebagian
        } else {
            // Jika belum bayar sama sekali, kembalikan ke sent/draft
            // (Asumsi default 'sent' jika sudah ada tagihan tapi belum bayar)
            $status = $this->status === 'draft' ? 'draft' : 'sent';
        }

        // Update status di database tanpa mentrigger event 'updated' berulang kali
        $this->updateQuietly(['status' => $status]);
    }
}
