<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Package;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Illuminate\Support\Facades\DB;

class PackageStockReservationService
{
    public function sync(Package $package, array $items): void
    {
        DB::transaction(function () use ($package, $items): void {
            StockTransaction::$autoUpdateStock = false;

            try {
                $this->releaseExistingReservations($package);

                foreach ($this->productQuantities($items) as $productId => $quantity) {
                    $remaining = $quantity;

                    $stocks = ProductStock::query()
                        ->where('product_id', $productId)
                        ->where('qty_available', '>', 0)
                        ->orderBy('warehouse_id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($stocks as $stock) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $reservedQuantity = min((int) $stock->qty_available, $remaining);
                        $availableBefore = (int) $stock->qty_available;

                        $stock->decrement('qty_available', $reservedQuantity);
                        $stock->increment('qty_reserved', $reservedQuantity);

                        StockTransaction::create([
                            'transaction_code' => $this->transactionCode($package),
                            'transaction_date' => now(),
                            'product_id' => $productId,
                            'warehouse_id' => $stock->warehouse_id,
                            'mutation_type' => 'reserve',
                            'type' => 'keluar',
                            'quantity' => $reservedQuantity,
                            'price' => 0,
                            'total_price' => 0,
                            'stock_before' => $availableBefore,
                            'stock_after' => $availableBefore - $reservedQuantity,
                            'reference_id' => $package->id,
                            'reference_type' => Package::class,
                            'reference_number' => $package->package_code ?? $package->package_name,
                            'notes' => 'Booking stok untuk package',
                        ]);

                        $remaining -= $reservedQuantity;
                    }

                    if ($remaining > 0) {
                        throw new \RuntimeException("Stok product {$productId} tidak mencukupi untuk package.");
                    }
                }
            } finally {
                StockTransaction::$autoUpdateStock = true;
            }
        });
    }

    private function releaseExistingReservations(Package $package): void
    {
        $reservations = StockTransaction::query()
            ->where('reference_type', Package::class)
            ->where('reference_id', $package->id)
            ->whereIn('mutation_type', ['reserve', 'cancel'])
            ->select('product_id', 'warehouse_id')
            ->selectRaw("SUM(CASE WHEN mutation_type = 'reserve' THEN quantity ELSE -quantity END) as quantity")
            ->groupBy('product_id', 'warehouse_id')
            ->havingRaw('SUM(CASE WHEN mutation_type = \'reserve\' THEN quantity ELSE -quantity END) > 0')
            ->get();

        foreach ($reservations as $reservation) {
            $stock = ProductStock::query()
                ->where('product_id', $reservation->product_id)
                ->where('warehouse_id', $reservation->warehouse_id)
                ->lockForUpdate()
                ->first();

            $quantity = (int) $reservation->quantity;

            if (! $stock || $stock->qty_reserved < $quantity) {
                throw new \RuntimeException('Booking stok package tidak konsisten dan tidak dapat diperbarui.');
            }

            $stock->decrement('qty_reserved', $quantity);
            $stock->increment('qty_available', $quantity);

            StockTransaction::create([
                'transaction_code' => $this->transactionCode($package),
                'transaction_date' => now(),
                'product_id' => $reservation->product_id,
                'warehouse_id' => $reservation->warehouse_id,
                'mutation_type' => 'cancel',
                'type' => 'masuk',
                'quantity' => $quantity,
                'price' => 0,
                'total_price' => 0,
                'stock_before' => (int) $stock->qty_available - $quantity,
                'stock_after' => (int) $stock->qty_available,
                'reference_id' => $package->id,
                'reference_type' => Package::class,
                'reference_number' => $package->package_code ?? $package->package_name,
                'notes' => 'Pelepasan booking stok package sebelum sinkronisasi',
            ]);
        }
    }

    private function productQuantities(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => ($item['item_type'] ?? null) === 'product')
            ->groupBy(fn (array $item): int => (int) $item['item_id'])
            ->map(fn ($productItems): int => $productItems->sum(fn (array $item): int => (int) $item['quantity']))
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();
    }

    private function transactionCode(Package $package): string
    {
        return 'PKG-RES-' . $package->id . '-' . now()->format('YmdHisv');
    }
}
