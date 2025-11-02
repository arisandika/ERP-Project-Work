<?php

namespace App\Filament\Resources\Inventory\ProductResource\Pages;

use App\Filament\Resources\Inventory\ProductResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Get next available ID (with gap filling)
        $nextId = Product::getNextAvailableId();
        
        // Prepare data for insertion
        $insertData = [
            'id_product' => $nextId,
            'product_name' => $data['product_name'],
            'category_id' => $data['category_id'],
            'label' => $data['label'] ?? 'product',
            'unit_id' => $data['unit_id'],
            'min_stock' => $data['min_stock'] ?? 0,
            'price' => $data['price'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Insert directly using DB to bypass guarded
        DB::table('nx_products')->insert($insertData);
        
        // Update auto-increment if we used a gap ID
        $maxId = Product::max('id_product');
        if ($maxId) {
            DB::statement("ALTER TABLE nx_products AUTO_INCREMENT = " . ($maxId + 1));
        }
        
        // Get created product
        $product = Product::find($nextId);
        
        // Handle initial stock if provided
        $initialStock = $data['initial_stock'] ?? 0;
        if ($initialStock > 0 && $product) {
            $this->createInitialStock($product, $initialStock);
        }
        
        // Return the created model
        return $product;
    }

    /**
     * Create initial stock for new product
     */
    protected function createInitialStock(Product $product, int $quantity): void
    {
        // Get or create default warehouse
        $warehouse = Warehouse::first();
        
        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'warehouse_name' => 'Gudang Utama',
                'location' => 'Lokasi Utama',
            ]);
        }

        // Create or update product stock directly (set, not increment)
        $productStock = \App\Models\Inventory\ProductStock::firstOrCreate(
            [
                'id_product' => $product->id_product,
                'id_warehouse' => $warehouse->id_warehouse,
            ],
            [
                'qty' => $quantity,
                'status' => $quantity > 0 ? 'available' : 'out_of_stock',
            ]
        );

        // If record already exists, update it
        if ($productStock->wasRecentlyCreated === false) {
            $productStock->qty = $quantity;
            $productStock->status = $quantity > 0 ? 'available' : 'out_of_stock';
            $productStock->save();
        }

        // Create stock transaction for audit trail (without notes)
        // Note: This will trigger the event listener, but since we already set the stock,
        // we need to prevent double update. We'll create transaction without triggering update
        // by creating it and then manually adjusting if needed.
        $transaction = new StockTransaction();
        $transaction->product_id = $product->id_product;
        $transaction->warehouse_id = $warehouse->id_warehouse;
        $transaction->transaction_date = now();
        $transaction->type = 'masuk';
        $transaction->quantity = $quantity;
        $transaction->notes = null; // No notes for initial stock
        
        // Temporarily disable events to prevent double update
        StockTransaction::withoutEvents(function () use ($transaction) {
            $transaction->save();
        });
        
        // Manually set the stock to ensure consistency (since we disabled events)
        // But actually we already set it above, so this is fine
    }
}
