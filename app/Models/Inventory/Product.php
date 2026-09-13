<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'nx_products';

    protected $fillable = [
        'product_code',
        'product_name',
        'category_id',
        'label',
        'unit_id',
        'min_stock',
        'max_stock',
        'price',
        'purchase_price',
        'selling_price',
        'image_path',
        'is_serialized',
        'is_web_published',
    ];

    protected $appends = ['image_url'];

    // === AUTO-GENERATE PRODUCT CODE ===
    protected static function booted(): void
    {
        // Logic auto-generate saat create (sebelum simpan ke DB)
        static::creating(function (Product $product) {
            if (empty($product->product_code)) {
                // Generate kode temporary pakai uniqid biar aman dari race condition
                // Nanti bisa di-update jadi ID based setelah insert via 'created' event
                $product->product_code = 'TEMP-' . strtoupper(uniqid());
            }
        });

        // Update kode jadi format BRG-XXXXXX setelah ID tersedia
        static::created(function (Product $product) {
            if (str_starts_with($product->product_code, 'TEMP-')) {
                $product->product_code = 'BRG-' . str_pad($product->id, 6, '0', STR_PAD_LEFT);
                $product->saveQuietly();
            }
        });
    }

    // === RELASI ===

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class, 'product_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_id');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'product_id', 'id');
    }

    // === ACCESSORS ===
    public function getAvailableStockAttribute(): int
    {
        return (int) $this->productStocks()->sum('qty_available');
    }

    public function getReservedStockAttribute(): int
    {
        return (int) $this->productStocks()->sum('qty_reserved');
    }

    public function getTotalStockAttribute(): int
    {
        return $this->available_stock + $this->reserved_stock;
    }

    // Accessor legacy (tetap ada untuk backward compatibility)
    public function getKodeBarangAttribute(): string
    {
        // Sekarang ambil dari kolom DB, kalau kosong baru generate
        return $this->product_code ?? 'BRG-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->available_stock < 10;
    }

    public function getIsOverStockAttribute(): bool
    {
        return $this->max_stock > 0 && $this->total_stock > $this->max_stock;
    }

    public function getStockThresholdAttribute(): int
    {
        return 10;
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            $path = collect(explode('/', $this->image_path))
                ->map(fn($segment) => rawurlencode($segment))
                ->implode('/');

            return Storage::disk('public')->url($path);
        }
        return 'https://thumbs2.imgbox.com/98/e9/y65t3ovR_t.png';
    }

    // === METHODS ===
    public static function getLowStockProducts()
    {
        return static::with(['unit', 'category', 'productStocks.warehouse'])
            ->whereHas('productStocks', function ($query) {
                $query->where('qty_available', '<=', 10);
            })
            ->get();
    }

    public function isLowStockInWarehouse(?int $warehouseId = null): bool
    {
        $query = $this->productStocks();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        return $query->where('qty_available', '<', $this->stock_threshold)->exists();
    }

    public function getStockStatusLabel(?int $qty = null): string
    {
        $checkQty = $qty ?? $this->available_stock;
        return match (true) {
            $checkQty <= 0  => 'OUT OF STOCK',
            $checkQty <= 5  => 'CRITICAL',
            $checkQty <= 10 => 'LOW',
            default         => 'AVAILABLE',
        };
    }

    public function getStockStatusColor(?int $qty = null): string
    {
        $checkQty = $qty ?? $this->available_stock;
        return match (true) {
            $checkQty <= 0  => 'danger',
            $checkQty <= 5  => 'danger',
            $checkQty <= 10 => 'warning',
            default         => 'success',
        };
    }
}
