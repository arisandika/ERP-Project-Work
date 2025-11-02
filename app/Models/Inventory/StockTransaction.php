<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Events\Created;
use Illuminate\Database\Eloquent\Events\Updated;
use Illuminate\Database\Eloquent\Events\Deleted;

class StockTransaction extends Model
{
    use HasFactory;

    protected $table = 'nx_stock_transactions';
    protected $primaryKey = 'id_transaction';
    protected $guarded = ['id_transaction'];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->updateProductStock('created');
        });

        static::updated(function ($transaction) {
            $transaction->updateProductStock('updated');
        });

        static::deleted(function ($transaction) {
            $transaction->updateProductStock('deleted');
        });
    }

    // Relasi BelongsTo: Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id_product');
    }

    // Relasi BelongsTo: Warehouse
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id_warehouse');
    }

    /**
     * Get or create default warehouse
     */
    protected function getDefaultWarehouse(): \App\Models\Inventory\Warehouse
    {
        $defaultWarehouse = \App\Models\Inventory\Warehouse::first();
        
        if (!$defaultWarehouse) {
            // Create default warehouse if not exists
            $defaultWarehouse = \App\Models\Inventory\Warehouse::create([
                'warehouse_name' => 'Gudang Utama',
                'location' => 'Lokasi Utama',
            ]);
        }
        
        return $defaultWarehouse;
    }

    /**
     * Update product stock based on transaction
     */
    protected function updateProductStock(string $event): void
    {
        if (!$this->product_id) {
            return;
        }

        // Get default warehouse (warehouse_id might be null, use default)
        $warehouseId = $this->warehouse_id ?? $this->getDefaultWarehouse()->id_warehouse;

        if ($event === 'created') {
            // Transaksi baru: masuk = tambah, keluar = kurang
            $quantityChange = $this->type === 'masuk' ? $this->quantity : -$this->quantity;
            $this->updateStockForWarehouse($warehouseId, $quantityChange);
            
        } elseif ($event === 'updated') {
            // Transaksi diupdate: perlu reverse perubahan lama dan apply perubahan baru
            $original = $this->getOriginal();
            $oldQuantity = $original['quantity'] ?? 0;
            $oldType = $original['type'] ?? '';
            $oldWarehouseId = $original['warehouse_id'] ?? $this->getDefaultWarehouse()->id_warehouse;
            $newWarehouseId = $this->warehouse_id ?? $this->getDefaultWarehouse()->id_warehouse;
            
            // Reverse perubahan lama di warehouse lama
            $oldChange = $oldType === 'masuk' ? -$oldQuantity : $oldQuantity;
            $this->updateStockForWarehouse($oldWarehouseId, $oldChange);
            
            // Apply perubahan baru di warehouse baru
            $newChange = $this->type === 'masuk' ? $this->quantity : -$this->quantity;
            $this->updateStockForWarehouse($newWarehouseId, $newChange);
            
        } elseif ($event === 'deleted') {
            // Transaksi dihapus: reverse perubahan
            $quantityChange = $this->type === 'masuk' ? -$this->quantity : $this->quantity;
            $this->updateStockForWarehouse($warehouseId, $quantityChange);
        }
    }

    /**
     * Update stock for specific warehouse
     */
    protected function updateStockForWarehouse(int $warehouseId, int $quantityChange): void
    {
        // Update atau create product stock
        $productStock = \App\Models\Inventory\ProductStock::firstOrCreate(
            [
                'id_product' => $this->product_id,
                'id_warehouse' => $warehouseId,
            ],
            [
                'qty' => 0,
                'status' => 'available',
            ]
        );

        $productStock->increment('qty', $quantityChange);

        // Update status based on stock
        if ($productStock->qty <= 0) {
            $productStock->status = 'out_of_stock';
        } else {
            $productStock->status = 'available';
        }
        
        $productStock->save();
    }
}

