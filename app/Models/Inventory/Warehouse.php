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
        // Jika sudah di-load dengan withCount dari Filament, gunakan attribute tersebut
        if (array_key_exists('total_products', $this->attributes)) {
            return (int) $this->total_products;
        }
        return $this->stocks()->distinct('product_id')->count('product_id');
    }

    // Get total stock quantity in warehouse
    public function getTotalStockAttribute(): int
    {
        // Cek apakah data aggregate dari Filament Table (withSum) sudah ada untuk performa
        if (array_key_exists('sum_qty_available', $this->attributes)) {
            return ($this->sum_qty_available ?? 0) +
                   ($this->sum_qty_reserved ?? 0) +
                   ($this->sum_qty_on_delivery ?? 0);
        }

        // Fallback untuk Infolist / pemanggilan biasa
        return (int) $this->stocks()
            ->selectRaw('COALESCE(SUM(qty_available), 0) + COALESCE(SUM(qty_reserved), 0) + COALESCE(SUM(qty_on_delivery), 0) as total')
            ->value('total');
    }

    // Cek apakah gudang memiliki stok (untuk proteksi delete)
    public function hasStocks(): bool
    {
        return $this->stocks()->exists();
    }

    // Get low stock items in this warehouse
    public function getLowStockItemsAttribute()
    {
        return $this->stocks()
            ->with('product')
            ->get()
            ->filter(function ($stock) {
                // Asumsi field qty_available yang digunakan untuk cek threshold
                $threshold = 10;
                return $stock->qty_available < $threshold;
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
