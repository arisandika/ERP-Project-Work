<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use SoftDeletes;
    protected $table = 'nx_sales_order_items';

    protected $fillable = [
        'nx_sales_order_id',
        'item_type',
        'item_id',
        'item_code',
        'item_name',
        'qty',
        'unit_price',
        'line_total',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'nx_sales_order_id', 'id');
    }

    protected static function booted(): void
    {
        static::creating(function (SalesOrderItem $item) {
            if ($item->line_total === null) {
                $qty   = (float) ($item->qty ?? 0);
                $price = (float) ($item->unit_price ?? 0);
                $item->line_total = $qty * $price;
            }
        });

        static::updating(function (SalesOrderItem $item) {
            if ($item->isDirty(['qty', 'unit_price']) || $item->line_total === null) {
                $qty   = (float) ($item->qty ?? 0);
                $price = (float) ($item->unit_price ?? 0);
                $item->line_total = $qty * $price;
            }
        });
    }

}
