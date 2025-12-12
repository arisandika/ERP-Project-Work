<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use SoftDeletes;

    protected $table = 'nx_invoice_items';

    protected $fillable = [
        'nx_invoice_id',
        'item_type',
        'item_id',
        'item_code',
        'item_name',
        'qty',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'nx_invoice_id');
    }

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            if ($item->line_total === null) {
                $qty   = (float) ($item->qty ?? 0);
                $price = (float) ($item->unit_price ?? 0);
                $item->line_total = $qty * $price;
            }
        });

        static::updating(function (InvoiceItem $item) {
            if ($item->isDirty(['qty', 'unit_price']) || $item->line_total === null) {
                $qty   = (float) ($item->qty ?? 0);
                $price = (float) ($item->unit_price ?? 0);
                $item->line_total = $qty * $price;
            }
        });
    }
}
