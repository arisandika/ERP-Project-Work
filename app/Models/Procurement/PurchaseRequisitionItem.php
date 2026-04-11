<?php

namespace App\Models\Procurement;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    protected $table = 'nx_purchase_requisition_items';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_price' => 'decimal:2',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
