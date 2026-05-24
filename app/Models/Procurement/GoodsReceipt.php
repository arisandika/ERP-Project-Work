<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Models\Inventory\Warehouse;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class GoodsReceipt extends Model
{
    use SoftDeletes, GeneratesDocumentNumber;

    protected $table = 'nx_goods_receipts';

    protected $guarded = ['id'];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->gr_number)) {
                $model->gr_number = self::generateGRNumber();
            }

            if (blank($model->received_by)) {
                $model->received_by = Auth::id() ?? 1; // Fallback ke admin jika Auth kosong (misal saat seeder berjalan)
            }

            if (blank($model->status)) {
                $model->status = self::STATUS_DRAFT;
            }
        });
    }

    /**
     * Memanggil Trait untuk generate nomor GR.
     */
    public static function generateGRNumber(): string
    {
        return self::generateDocNumber('GR', 'gr_number');
    }
}
