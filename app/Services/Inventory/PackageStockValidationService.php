<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\Package;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Illuminate\Validation\ValidationException;

class PackageStockValidationService
{
    public function normalizeAndValidate(array $items, ?Package $package = null): array
    {
        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => 'Pastikan product yang ingin dimasukkan ke dalam package memiliki quantity yang tersedia.',
            ]);
        }

        $mergedProducts = [];
        $serviceItems   = [];
        $otherItems     = [];

        foreach ($items as $index => $item) {
            $type = $item['item_type'] ?? null;

            if ($type === 'service') {
                $item['quantity'] = 1;
                $serviceItems[] = $item;
                continue;
            }

            if ($type !== 'product') {
                // tipe tidak dikenali, biarkan lolos apa adanya (atau bisa juga dilempar error)
                $otherItems[] = $item;
                continue;
            }

            $productId = (int) ($item['item_id'] ?? 0);
            $quantity  = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Product dan quantity wajib diisi dengan benar.',
                ]);
            }

            // Merge baris dengan product yang sama
            if (isset($mergedProducts[$productId])) {
                $mergedProducts[$productId]['quantity'] += $quantity;
            } else {
                $mergedProducts[$productId] = $item;
                $mergedProducts[$productId]['quantity'] = $quantity;
            }
        }

        // Recalculate subtotal setelah merge (harga per unit dianggap sama untuk product yang sama)
        foreach ($mergedProducts as $productId => &$item) {
            $price = (float) ($item['price'] ?? 0);
            $item['subtotal'] = $price * $item['quantity'];
        }
        unset($item);

        // Validasi stok berdasarkan quantity yang sudah digabung
        foreach ($mergedProducts as $productId => $item) {
            $requiredQuantity = $item['quantity'];

            $product = Product::find($productId);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => "Product dengan ID {$productId} tidak ditemukan.",
                ]);
            }

            $availableQuantity = (int) ProductStock::query()
                ->where('product_id', $productId)
                ->selectRaw('COALESCE(SUM(qty_available), 0) as total')
                ->value('total');

            if ($package) {
                $availableQuantity += (int) StockTransaction::query()
                    ->where('reference_type', Package::class)
                    ->where('reference_id', $package->id)
                    ->whereIn('mutation_type', ['reserve', 'cancel'])
                    ->where('product_id', $productId)
                    ->selectRaw("COALESCE(SUM(CASE WHEN mutation_type = 'reserve' THEN quantity ELSE -quantity END), 0) as total")
                    ->value('total');
            }

            if ($availableQuantity < $requiredQuantity) {
                throw ValidationException::withMessages([
                    'items' => "Stock product '{$product->product_name}' tidak mencukupi. Tersedia: {$availableQuantity}, dibutuhkan: {$requiredQuantity}.",
                ]);
            }
        }

        // Gabungkan kembali: product (sudah di-merge) + service + lainnya
        return array_values(array_merge($mergedProducts, $serviceItems, $otherItems));
    }
}