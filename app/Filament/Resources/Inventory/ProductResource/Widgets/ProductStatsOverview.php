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

        $lowStockProducts = Product::query()
            ->whereRaw('(
                SELECT COALESCE(SUM(qty), 0)
                FROM nx_product_stock
                WHERE nx_product_stock.id_product = nx_products.id_product
            ) BETWEEN 1 AND 10')
            ->count();

        $outOfStockProducts = Product::query()
            ->whereRaw('(
                SELECT COALESCE(SUM(qty), 0)
                FROM nx_product_stock
                WHERE nx_product_stock.id_product = nx_products.id_product
            ) <= 0')
            ->count();

        return [
            Stat::make('Total Barang', (string) $totalProducts)
                ->description('Seluruh produk terdaftar')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Stok Rendah', (string) $lowStockProducts)
                ->description('Produk dengan stok 1–10 unit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Stok Habis', (string) $outOfStockProducts)
                ->description('Produk dengan stok 0 atau belum ada stok')
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}

