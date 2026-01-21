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
        'unit_price'       => 'integer',
        'discount'         => 'integer',
        'tax'              => 'integer',
        'line_total'         => 'integer',
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
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
