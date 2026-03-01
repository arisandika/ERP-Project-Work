<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'nx_delivery_order_items';

    protected $fillable = [
        'nx_delivery_order_id',
        'item_type',
        'item_id',
        'item_code',
        'item_name',
        'scanned_sns',
        'qty',
        'qty_ordered',
        'qty_remaining',
    ];

    protected $casts = [
        'qty'           => 'integer',
        'qty_ordered'   => 'integer',
        'qty_remaining' => 'integer',
    ];


    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'nx_delivery_order_id', 'id');
    }

    public function item()
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }
}
