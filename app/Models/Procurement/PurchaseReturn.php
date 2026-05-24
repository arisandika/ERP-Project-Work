<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\GeneratesDocumentNumber;
use App\Models\User;

class PurchaseReturn extends Model
{
    // 1. Tambahkan Trait di sini
    use HasFactory, SoftDeletes, GeneratesDocumentNumber;

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
        static::creating(function (self $model) {
            // Pengecekan auth yang lebih rapi
            if (blank($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (blank($model->return_number)) {
                $model->return_number = self::generateReturnNumber();
            }
        });
    }

    /**
     * Generate nomor otomatis format PRT-2605-001
     */
    public static function generateReturnNumber(): string
    {
        return self::generateDocNumber('PRT', 'return_number');
    }

    /**
     * Karena metode dari Trait kita tidak langsung menyimpan ke database (hanya SELECT),
     * kita bisa menggunakan fungsi yang persis sama untuk preview di form Filament.
     */
    public static function previewNextReturnNumber(): string
    {
        return self::generateDocNumber('PRT', 'return_number');
    }
}
