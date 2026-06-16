<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'nx_invoices';

    protected $fillable = [
        'nx_sales_order_id',
        'nx_customer_id',
        'nx_employee_id',
        'customer_po_number',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'total_paid',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'nx_invoice_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'nx_invoice_id');
    }

    public function getRemainingBalanceAttribute(): float
    {
        $remaining = (float) $this->grand_total - (float) $this->total_paid;

        return max(0, round($remaining, 2));
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return in_array($this->status, ['sent', 'partial'], true)
            && Carbon::parse($this->due_date)->isPast();
    }

    public function recalculateStatus(): void
    {
        $totalPaid = (float) $this->payments()
            ->whereIn('status', ['paid', 'settlement', 'success'])
            ->sum('amount');

        $grandTotal = (float) $this->grand_total;
        $tolerance = 0.01;

        if ($totalPaid >= ($grandTotal - $tolerance) && $grandTotal > 0) {
            $status = 'paid';
            $totalPaid = $grandTotal;
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        } else {
            $status = $this->status === 'draft' ? 'draft' : 'sent';
        }

        $this->updateQuietly([
            'total_paid' => round($totalPaid, 2),
            'status' => $status,
        ]);

        if ($this->salesOrder && $status === 'paid') {
            if (!in_array($this->salesOrder->status, ['completed', 'cancelled'], true)) {
                $this->salesOrder->update([
                    'status' => 'paid',
                ]);
            }
        }
    }

    public static function generateInvoiceNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'INV';

        $prefixLike = "%/{$code}/{$company}/{$roman}/{$year}";

        $last = self::withTrashed()
            ->where('invoice_number', 'like', $prefixLike)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) ($parts[0] ?? 0)) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if ($invoice->invoice_date) {
                $invoice->invoice_date = Carbon::parse($invoice->invoice_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }

            $invoice->subtotal = round((float) ($invoice->subtotal ?? 0), 2);
            $invoice->discount = round((float) ($invoice->discount ?? 0), 2);
            $invoice->tax = max(0, min(round((float) ($invoice->tax ?? 0), 2), 100));
            $invoice->grand_total = round((float) ($invoice->grand_total ?? 0), 2);
            $invoice->total_paid = round((float) ($invoice->total_paid ?? 0), 2);
        });

        static::updating(function (Invoice $invoice) {
            $invoice->subtotal = round((float) ($invoice->subtotal ?? 0), 2);
            $invoice->discount = round((float) ($invoice->discount ?? 0), 2);
            $invoice->tax = max(0, min(round((float) ($invoice->tax ?? 0), 2), 100));
            $invoice->grand_total = round((float) ($invoice->grand_total ?? 0), 2);
            $invoice->total_paid = round((float) ($invoice->total_paid ?? 0), 2);
        });
    }
}
