<?php

namespace App\Filament\Resources\Inventory\ProductResource\Pages;

use App\Filament\Resources\Inventory\ProductResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return 'Tambah Product';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle record creation dengan logic product_code auto-generate
     * Logic auto-generate sudah ada di Model::booted(), jadi tidak perlu custom ID
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Extract productStocks dari data (jika ada di Repeater)
        $productStocksData = $data['productStocks'] ?? [];
        unset($data['productStocks']); // Hapus dari data utama agar tidak error saat create

        // Create product (auto-increment ID & product_code via Model::booted())
        $product = Product::create($data);

        // Handle stock items dari Repeater (jika ada)
        if (!empty($productStocksData)) {
            foreach ($productStocksData as $stockItem) {
                $this->createProductStock($product, $stockItem);
            }
        }

        return $product;
    }

    /**
     * Create product stock entry & transaction
     */
    protected function createProductStock(Product $product, array $stockData): void
    {
        $warehouseId = $stockData['warehouse_id'] ?? null;
        $qty = (int) ($stockData['qty'] ?? 0);
        $status = $stockData['status'] ?? 'available';

        if (!$warehouseId) {
            return; // Skip jika warehouse tidak valid
        }

        // Create ProductStock record
        $productStock = ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'qty' => $qty,
            'status' => $status,
        ]);

        // Create audit trail (StockTransaction) jika qty > 0
        if ($qty > 0) {
            StockTransaction::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'transaction_date' => now(),
                'type' => 'masuk',
                'quantity' => $qty,
                'notes' => 'Stok awal saat produk dibuat',
            ]);
        }
    }

    /**
     * Customize notification message setelah create
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Produk berhasil ditambahkan!';
    }
}
