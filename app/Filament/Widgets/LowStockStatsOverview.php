<?php

namespace App\Filament\Widgets;

use App\Models\Inventory\ProductStock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // REVISI: Mengganti qty menjadi qty_available
        $lowStockCount = ProductStock::where('qty_available', '<=', 10)->count();

        $outOfStockCount = ProductStock::where('qty_available', '<=', 0)->count();

        $lowStockProducts = ProductStock::where('qty_available', '<=', 10)
            ->distinct('product_id')
            ->count('product_id');

        return [
            Stat::make('Product Stock Kritis', $lowStockProducts)
                ->description('Product unique dengan stock tersedia rendah')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([7, 8, 6, 9, 10, 12, $lowStockProducts]),

            Stat::make('Total Akses Gudang Stock Rendah', $lowStockCount)
                ->description('Tersebar di berbagai warehouse')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning')
                ->chart([5, 7, 6, 8, 9, 10, $lowStockCount]),

            Stat::make('Stock Tersedia Habis', $outOfStockCount)
                ->description('Item dengan qty siap jual = 0')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->chart([3, 2, 4, 3, 2, 1, $outOfStockCount]),
        ];
    }

    public static function canView(): bool
    {
        // REVISI: Mengganti qty menjadi qty_available
        return ProductStock::where('qty_available', '<=', 10)->exists();
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }
}
