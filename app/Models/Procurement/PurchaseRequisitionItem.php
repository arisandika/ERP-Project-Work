<?php

namespace App\Models\Procurement;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionItem extends Model
{
    protected $table = 'nx_purchase_requisition_items';
    protected $guarded = ['id'];
    protected $casts = ['estimated_price' => 'decimal:2'];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
