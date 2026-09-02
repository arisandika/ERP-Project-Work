<?php

namespace App\Services\Sales;

use App\Models\Finance\FinancialRecord;
use App\Models\Inventory\Product;
use App\Models\Inventory\Package;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceStockService
{
    public function process(Invoice $invoice): void
    {
        if (StockTransaction::query()
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('mutation_type', 'adjustment_out')
            ->exists()) {
            return;
        }

        $warehouseId = Warehouse::query()
            ->where('warehouse_name', 'Gudang Utama')
            ->value('id') ?? 1;
        $totalCost = 0;
        $productItemProcessed = false;

        foreach ($invoice->items as $item) {
            $itemType = $item->item_type ?: 'product';
            $itemQuantity = (int) $item->qty;

            if ($itemQuantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => "Qty item '{$item->item_name}' harus lebih dari 0.",
                ]);
            }

            if ($itemType === 'product') {
                $components = [[
                    'product_id' => $item->item_id,
                    'quantity' => $itemQuantity,
                    'name' => $item->item_name,
                ]];
            } elseif ($itemType === 'package') {
                $package = Package::with('items')->find($item->item_id);
                if (! $package) {
                    throw ValidationException::withMessages([
                        'items' => "Package '{$item->item_name}' tidak ditemukan.",
                    ]);
                }

                $components = $package->items
                    ->where('item_type', 'product')
                    ->map(fn ($component) => [
                        'product_id' => $component->item_id,
                        'quantity' => $itemQuantity * (int) $component->quantity,
                        'name' => $package->package_name,
                    ])
                    ->values()
                    ->all();
            } else {
                continue;
            }

            foreach ($components as $component) {
                $product = Product::find($component['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Product untuk item '{$component['name']}' tidak ditemukan.",
                    ]);
                }

                $quantity = (int) $component['quantity'];
                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Qty product '{$product->product_name}' harus lebih dari 0.",
                    ]);
                }

                $cost = (float) ($product->purchase_price ?? 0);
                $productItemProcessed = true;

                StockTransaction::create([
                    'transaction_code' => $this->generateTransactionCode(),
                    'transaction_date' => $invoice->invoice_date ?? now(),
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'mutation_type' => 'adjustment_out',
                    'type' => 'keluar',
                    'quantity' => $quantity,
                    'price' => $cost,
                    'total_price' => $cost * $quantity,
                    'reference_id' => $invoice->id,
                    'reference_type' => Invoice::class,
                    'reference_number' => $invoice->invoice_number,
                    'notes' => $itemType === 'package'
                        ? 'Pengurangan stok komponen package saat invoice dibuat'
                        : 'Pengurangan stok saat invoice dibuat',
                    'created_by' => auth()->user()?->id,
                ]);

                $totalCost += $cost * $quantity;
            }
        }

        if (! $productItemProcessed) {
            return;
        }

        FinancialRecord::firstOrCreate(
            [
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'category' => 'Cost of Goods Sold',
            ],
            [
                'transaction_date' => $invoice->invoice_date ?? now(),
                'type' => 'pengeluaran',
                'amount' => $totalCost,
                'description' => 'HPP dari pengurangan stok invoice ' . $invoice->invoice_number,
                'reference_number' => $invoice->invoice_number,
                'created_by' => auth()->user()?->employee?->id,
            ]
        );
    }

    private function generateTransactionCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $month = (int) now()->month;
            $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1];
            $prefix = "ST-OUT/NEX/{$romanMonth}/{$year}";
            $last = StockTransaction::query()
                ->where('transaction_code', 'like', "%/{$prefix}")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('transaction_code');

            $sequence = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

            return sprintf('%04d/%s', $sequence, $prefix);
        });
    }
}