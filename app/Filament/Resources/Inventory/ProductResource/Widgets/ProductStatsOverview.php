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
                WHERE nx_product_stock.product_id = nx_products.id
            ) BETWEEN 1 AND 10')
            ->count();

        $outOfStockProducts = Product::query()
            ->whereRaw('(
                SELECT COALESCE(SUM(qty), 0)
                FROM nx_product_stock
                WHERE nx_product_stock.product_id = nx_products.id
            ) <= 0')
            ->count();

        return [
            Stat::make('Total Product', (string) $totalProducts)
                ->description('Seluruh Product terdaftar')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Stock Rendah', (string) $lowStockProducts)
                ->description('Product dengan stock 1–10 unit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Stock Habis', (string) $outOfStockProducts)
                ->description('Product dengan stock 0 atau stock habis')
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}

