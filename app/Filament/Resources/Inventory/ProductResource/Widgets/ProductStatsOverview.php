<?php

namespace App\Filament\Resources\Inventory\ProductResource\Widgets;

use App\Models\Inventory\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();

        // REVISI: Mengubah SUM(qty) menjadi SUM(qty_available)
        $lowStockProducts = Product::query()
            ->whereRaw('(
                SELECT COALESCE(SUM(qty_available), 0)
                FROM nx_product_stock
                WHERE nx_product_stock.product_id = nx_products.id
            ) BETWEEN 1 AND 10')
            ->count();

        // REVISI: Mengubah SUM(qty) menjadi SUM(qty_available)
        $outOfStockProducts = Product::query()
            ->whereRaw('(
                SELECT COALESCE(SUM(qty_available), 0)
                FROM nx_product_stock
                WHERE nx_product_stock.product_id = nx_products.id
            ) <= 0')
            ->count();

        // TAMBAHAN ARSITEKTUR: Menghitung total unit barang yang di-reserve (dipesan) di seluruh gudang
        $totalReserved = \App\Models\Inventory\ProductStock::sum('qty_reserved');

        return [
            Stat::make('Total Product', (string) $totalProducts)
                ->description('Seluruh jenis Product terdaftar')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Stock Tersedia Rendah', (string) $lowStockProducts)
                ->description('Product dengan stock siap jual 1–10 unit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Stock Tersedia Habis', (string) $outOfStockProducts)
                ->description('Product dengan stock siap jual 0')
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            // TAMBAHAN ARSITEKTUR: Kartu baru untuk menampilkan metrik pesanan yang tertahan di gudang
            Stat::make('Total Unit Dipesan (Reserved)', (string) $totalReserved)
                ->description('Unit menunggu proses pengiriman')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-shopping-bag')
                ->color('info'),
        ];
    }
}
