<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use Illuminate\Validation\ValidationException;

class PackageStockValidationService
{
    public function normalizeAndValidate(array $items): array
    {
        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => 'Pastikan product yang ingin dimasukkan ke dalam package memiliki quantity yang available.',
            ]);
        }

        $requiredProducts = [];

        foreach ($items as $index => &$item) {
            $type = $item['item_type'] ?? null;

            if ($type === 'service') {
                $item['quantity'] = 1;
                continue;
            }

            if ($type !== 'product') {
                continue;
            }

            $productId = (int) ($item['item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Product dan quantity wajib diisi dengan benar.',
                ]);
            }

            $requiredProducts[$productId] = ($requiredProducts[$productId] ?? 0) + $quantity;
        }
        unset($item);

        foreach ($requiredProducts as $productId => $requiredQuantity) {
            $product = Product::find($productId);
            $availableQuantity = (int) ProductStock::query()
                ->where('product_id', $productId)
                ->sum('qty_available');

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => "Product dengan ID {$productId} tidak ditemukan.",
                ]);
            }

            if ($availableQuantity < $requiredQuantity) {
                throw ValidationException::withMessages([
                    'items' => "Stock product '{$product->product_name}' tidak mencukupi. Tersedia: {$availableQuantity}, dibutuhkan: {$requiredQuantity}.",
                ]);
            }
        }

        return $items;
    }
}