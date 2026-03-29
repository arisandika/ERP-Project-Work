<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class GoodsReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'nx_goods_receipts';
    protected $guarded = ['id'];
    protected $casts = [
        'receipt_date' => 'date',
    ];

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

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->gr_number)) {
                $model->gr_number = self::generateGRNumber();
            }
            if (empty($model->received_by)) {
                $model->received_by = Auth::id() ?? 1;
            }
        });
    }

    public static function generateGRNumber(): string
    {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        $month = now()->month;
        $year = now()->year;
        $company = 'NEX';
        $code = 'GR';

        $prefix = "{$code}/{$company}/{$romanMonths[$month]}/{$year}";

        $lastGr = self::withTrashed()->where('gr_number', 'like', "%/{$prefix}")->orderByDesc('id')->first();
        $sequence = $lastGr ? ((int) explode('/', $lastGr->gr_number)[0]) + 1 : 1;

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }
}
