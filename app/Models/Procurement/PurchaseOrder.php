<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Enums\Procurement\PurchaseOrderStatus;

class PurchaseOrder extends Model
{
    use GeneratesDocumentNumber;

    protected $table = 'nx_purchase_orders';

    protected $fillable = [
        'po_number',
        'title',
        'supplier_id',
        'purchase_requisition_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'status' => PurchaseOrderStatus::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'purchase_order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\PurchaseOrderPayment::class, 'purchase_order_id');
    }

    public static function generatePONumber(): string
    {
        return self::generateDocNumber('PO', 'po_number');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->po_number)) {
                $model->po_number = self::generatePONumber();
            }

            if (blank($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (blank($model->status)) {
                $model->status = \App\Enums\Procurement\PurchaseOrderStatus::DRAFT;
            }

        });

        // Trigger otomatis untuk mengubah PR menjadi Completed saat PO dibuat
        static::created(function (self $model): void {
            if ($model->purchase_requisition_id) {
                PurchaseRequisition::whereKey($model->purchase_requisition_id)
                    ->update([
                        'status' => \App\Models\Procurement\PurchaseRequisition::STATUS_COMPLETED,
                    ]);
            }
        });
    }
}
