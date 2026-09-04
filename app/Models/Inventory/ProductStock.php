<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'nx_product_stock';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'qty_available',
        'qty_reserved',
        'qty_on_delivery',
        'sold_stock',
    ];

    // Relasi BelongsTo: Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relasi BelongsTo: Warehouse
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function unitName()
    {
        return $this->product->unit->unit_name ?? null;
    }
}
