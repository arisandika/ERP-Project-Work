<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use SoftDeletes, GeneratesDocumentNumber;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

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
        'status' => \App\Enums\Procurement\PurchaseInvoiceStatus::class,
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

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\PurchaseOrderPayment::class, 'purchase_invoice_id');
    }

    public function recalculateStatus(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $grandTotal = (float) $this->grand_total;

        // Penggunaan tolerance 0.01 sangat bagus untuk mengatasi isu floating point!
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

    // 2. Ganti isi function ini untuk memanggil Trait
    public static function generatePINumber(): string
    {
        return self::generateDocNumber('PI', 'invoice_number');
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
