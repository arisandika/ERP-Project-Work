<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventorySummaryOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalWarehouses = Warehouse::count();
        $totalStockQty = (int) ProductStock::sum('qty');

        return [
            Stat::make('Total Produk', (string) $totalProducts)
                ->description('Seluruh produk terdaftar')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Total Gudang', (string) $totalWarehouses)
                ->description('Gudang aktif terdaftar')
                ->icon('heroicon-o-building-storefront')
                ->color('info'),

            Stat::make('Total Qty Stok', (string) $totalStockQty)
                ->description('Akumulasi semua warehouse')
                ->icon('heroicon-o-archive-box')
                ->color('success'),
        ];
    }
}


