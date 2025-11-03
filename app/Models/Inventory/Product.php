<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany; // Untuk Polymorphic
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'nx_products';
    protected $primaryKey = 'id_product';
    protected $guarded = ['id_product'];

    // --- Relasi BelongsTo ---
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // --- Relasi HasMany ---
    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'id_product');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\StockTransaction::class, 'product_id', 'id_product');
    }

    // --- Relasi Polymorphic ---
    // Product bisa ada di banyak package items
    public function packageItems(): MorphMany
    {
        return $this->morphMany(Sales\PackageItem::class, 'item');
    }

    // --- Accessor untuk total stok ---
    public function getTotalStockAttribute(): int
    {
        return $this->productStocks()->sum('qty');
    }

    // --- Accessor untuk kode barang ---
    public function getKodeBarangAttribute(): string
    {
        return 'BRG-' . str_pad($this->id_product, 6, '0', STR_PAD_LEFT);
    }

    protected $appends = ['image_url'];

    // --- Accessor untuk status low stock ---
    public function getIsLowStockAttribute(): bool
    {
        $threshold = 10;
        return $this->total_stock < $threshold;
    }

    // --- Accessor untuk threshold stok ---
    public function getStockThresholdAttribute(): int
    {
        return 10;
    }

    // --- Method untuk get low stock items ---
    public static function getLowStockProducts()
    {
        return static::with(['unit', 'category', 'productStocks.warehouse'])
            ->whereHas('productStocks', function ($query) {
                $query->where('nx_product_stock.qty', '<=', 10);
            })
            ->get();
    }

    // --- Accessor untuk URL foto ---
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        return 'https://thumbs2.imgbox.com/98/e9/y65t3ovR_t.png';
    }

    // --- Method untuk check apakah produk ini low stock di warehouse tertentu ---
    public function isLowStockInWarehouse(?int $warehouseId = null): bool
    {
        $query = $this->productStocks();
        
        if ($warehouseId) {
            $query->where('id_warehouse', $warehouseId);
        }

        $threshold = $this->stock_threshold;
        
        return $query->where('qty', '<', $threshold)->exists();
    }

    // --- Method untuk get stock status label ---
    public function getStockStatusLabel(?int $qty = null): string
    {
        $checkQty = $qty ?? $this->total_stock;
        
        return match (true) {
            $checkQty <= 0 => 'OUT OF STOCK',
            $checkQty <= 5 => 'CRITICAL',
            $checkQty <= 10 => 'LOW',
            $checkQty < $this->stock_threshold => 'WARNING',
            default => 'AVAILABLE',
        };
    }

    // --- Method untuk get stock status color ---
    public function getStockStatusColor(?int $qty = null): string
    {
        $checkQty = $qty ?? $this->total_stock;
        
        return match (true) {
            $checkQty <= 0 => 'danger',
            $checkQty <= 5 => 'danger',
            $checkQty <= 10 => 'warning',
            $checkQty < $this->stock_threshold => 'warning',
            default => 'success',
        };
    }

    /**
     * Find the smallest available ID (gap filling)
     */
    public static function getNextAvailableId(): int
    {
        // Get all existing IDs
        $existingIds = static::pluck('id_product')->toArray();
        
        // If no records exist, start from 1
        if (empty($existingIds)) {
            return 1;
        }
        
        // Sort IDs
        sort($existingIds);
        
        // Find the first gap
        for ($i = 1; $i <= max($existingIds); $i++) {
            if (!in_array($i, $existingIds)) {
                return $i;
            }
        }
        
        // If no gap found, return next ID after max
        return max($existingIds) + 1;
    }
}