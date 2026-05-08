<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\User;
// Pastikan Anda mengimpor model Supplier dan GoodsReceipt yang sesuai dengan namespace Anda

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nx_purchase_returns';
    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class, 'supplier_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Procurement\GoodsReceipt::class, 'goods_receipt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseReturn $model) {

            if (auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (blank($model->return_number)) {
                $model->return_number = self::generateReturnNumber();
            }
        });
    }

    public static function previewNextReturnNumber(): string
    {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        $prefix = "PRT/NEX/" . $romanMonths[now()->month] . "/" . now()->year;

        $lastReturn = self::withTrashed()
            ->where('return_number', 'like', "%/{$prefix}")
            ->orderByDesc('id')
            ->first();

        $sequence = $lastReturn ? ((int) explode('/', $lastReturn->return_number)[0]) + 1 : 1;

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }

    private static function generateReturnNumber(): string
    {
        return DB::transaction(function () {
            $romanMonths = [
                1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            ];

            $prefix = "PRT/NEX/" . $romanMonths[now()->month] . "/" . now()->year;

            $lastReturn = self::withTrashed()
                ->where('return_number', 'like', "%/{$prefix}")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = $lastReturn ? ((int) explode('/', $lastReturn->return_number)[0]) + 1 : 1;

            return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
        });
    }
}
