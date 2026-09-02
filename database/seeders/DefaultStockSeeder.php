<?php

namespace Database\Seeders;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Seeder;

class DefaultStockSeeder extends Seeder
{
    /**
     * Seed default qty_available = 50 untuk produk pertama yang stoknya kosong.
     * Jalankan via: php artisan db:seed --class=DefaultStockSeeder
     */
    public function run(): void
    {
        $warehouse = Warehouse::where('warehouse_name', 'Gudang Utama')->first()
            ?? Warehouse::first();

        if (!$warehouse) {
            $this->command->warn('Warehouse tidak ditemukan.');
            return;
        }

        // Ambil 10 produk pertama yang tidak punya stok record sama sekali
        $productsNoStock = Product::whereDoesntHave('productStocks', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        })->limit(10)->get();

        foreach ($productsNoStock as $product) {
            ProductStock::create([
                'product_id'      => $product->id,
                'warehouse_id'      => $warehouse->id,
                'qty_available'     => 50,
            ]);

            $this->command->info("Seed stok: {$product->product_name} (ID {$product->id}) → 50");
        }

        // Update produk dengan stok 0 jadi 50 (hanya yang qty_available = 0)
        ProductStock::where('warehouse_id', $warehouse->id)
            ->where('qty_available', 0)
            ->update(['qty_available' => 50]);

        $this->command->info('Default stock seeded.');
    }
}
