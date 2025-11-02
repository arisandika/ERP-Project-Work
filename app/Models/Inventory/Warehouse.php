<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'nx_warehouses';
    protected $primaryKey = 'id_warehouse';
    protected $guarded = ['id_warehouse'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi: Warehouse memiliki banyak Stock records
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'id_warehouse');
    }

    // Accessor untuk kode gudang
    public function getWarehouseCodeAttribute(): string
    {
        return 'WH-' . str_pad($this->id_warehouse, 4, '0', STR_PAD_LEFT);
    }

    // Get total products in warehouse
    public function getTotalProductsAttribute(): int
    {
        return $this->stocks()->distinct('id_product')->count('id_product');
    }

    // Get total stock quantity in warehouse
    public function getTotalStockAttribute(): int
    {
        return $this->stocks()->sum('qty');
    }

    // Get low stock items in this warehouse
    public function getLowStockItemsAttribute()
    {
        return $this->stocks()
            ->with('product')
            ->get()
            ->filter(function ($stock) {
                $threshold = 10;
                return $stock->qty < $threshold;
            });
    }

    // Scope: Only active warehouses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}