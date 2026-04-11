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

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

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

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\PurchaseOrderPayment::class, 'purchase_invoice_id');
    }

    public function recalculateStatus(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $grandTotal = (float) $this->grand_total;
        $tolerance = 0.01;

        if ($totalPaid >= ($grandTotal - $tolerance)) {
            $status = self::STATUS_PAID;
        } elseif ($totalPaid > 0) {
            $status = self::STATUS_PARTIAL;
        } else {
            $status = self::STATUS_UNPAID;
        }

        $this->updateQuietly([
            'total_paid' => $totalPaid,
            'status' => $status,
        ]);
    }

    public static function generatePINumber(): string
    {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        $month = now()->month;
        $year = now()->year;
        $company = 'NEX';
        $code = 'PI';

        $prefix = "{$code}/{$company}/{$romanMonths[$month]}/{$year}";

        $lastPi = self::withTrashed()
            ->where('invoice_number', 'like', "%/{$prefix}")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;

        if ($lastPi?->invoice_number) {
            $parts = explode('/', $lastPi->invoice_number);
            $sequence = ((int) ($parts[0] ?? 0)) + 1;
        }

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->invoice_number)) {
                $model->invoice_number = self::generatePINumber();
            }

            if (blank($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (blank($model->status)) {
                $model->status = self::STATUS_UNPAID;
            }

            if (blank($model->total_paid)) {
                $model->total_paid = 0;
            }
        });
    }
}
