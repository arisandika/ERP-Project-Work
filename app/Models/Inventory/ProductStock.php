<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'nx_product_stock';
    protected $primaryKey = 'id_stock';
    protected $guarded = ['id_stock'];

    // Relasi BelongsTo: Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    // Relasi BelongsTo: Warehouse
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
}