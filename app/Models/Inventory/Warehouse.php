<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'nx_warehouses';

    protected $fillable = [
        'warehouse_name',
        'location',
        'manager_name',
        'phone',
        'maps_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['maps_link'];

    // Relasi: Warehouse memiliki banyak Stock records
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'warehouse_id');
    }

    // Accessor untuk kode gudang
    public function getWarehouseCodeAttribute(): string
    {
        return 'WH-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    // Get total products in warehouse
    public function getTotalProductsAttribute(): int
    {
        return $this->stocks()->distinct('product_id')->count('id');
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

    public function getMapsLinkAttribute(): ?string
    {
        return $this->maps_url ?: null;
    }

    // Scope: Only active warehouses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
