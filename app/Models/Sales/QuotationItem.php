<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Unit;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuotationItem extends Model
{
    use SoftDeletes;

    protected $table = 'nx_quotation_items';

    protected $fillable = [
        'nx_quotation_id',

        // polymorphic untuk menunjuk ke Product/Service/Package
        'item_type',
        'item_id',
        'item_name',
        'item_code',

        'description',
        'qty',
        'unit_id',

        'unit_price',
        'discount',
        'tax',
        'line_total',

        'sort',
        'extra_attributes',
    ];

    protected $casts = [
        'qty'              => 'integer',
        'unit_price'       => 'decimal:2',
        'discount'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'line_total'         => 'decimal:2',
        'extra_attributes' => 'array',
    ];

    /**
     * Relasi ke quotation induk
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'nx_quotation_id');
    }

    /**
     * Relasi polymorphic ke Product / Service / Package
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke tabel satuan (Unit of Measure)
     * TAMBAHKAN FUNGSI INI
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
