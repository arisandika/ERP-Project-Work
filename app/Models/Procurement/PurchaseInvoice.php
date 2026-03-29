<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'nx_purchase_invoices';
    protected $guarded = ['id'];
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Untuk nanti kita hubungkan dengan Pembayaran
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\PurchaseOrderPayment::class, 'purchase_invoice_id');
    }

    // Logika Auto-Update Status (Sama seperti Sales Invoice)
    public function recalculateStatus(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $grandTotal = (float) $this->grand_total;
        $tolerance = 0.01;

        if ($totalPaid >= ($grandTotal - $tolerance)) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        $this->updateQuietly([
            'total_paid' => $totalPaid,
            'status' => $status
        ]);
    }

    public static function generatePINumber(): string
    {
        $romanMonths = [1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI', 7=>'VII', 8=>'VIII', 9=>'IX', 10=>'X', 11=>'XI', 12=>'XII'];
        $month = now()->month;
        $year = now()->year;
        $company = 'NEX';
        $code = 'PI';

        $prefix = "{$code}/{$company}/{$romanMonths[$month]}/{$year}";

        $lastPi = self::withTrashed()->where('invoice_number', 'like', "%/{$prefix}")->orderByDesc('id')->first();
        $sequence = $lastPi ? ((int) explode('/', $lastPi->invoice_number)[0]) + 1 : 1;

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generatePINumber();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id() ?? 1;
            }
        });
    }
}
