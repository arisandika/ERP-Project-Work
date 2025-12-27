<?php

namespace App\Filament\Widgets;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockStatsOverview extends BaseWidget
{
    // use HasPageShield;
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hitung produk dengan stock kritis (qty < threshold)
        $lowStockCount = ProductStock::where('qty', '<=', 10)->count();

        // Hitung produk yang benar-benar habis (qty = 0)
        $outOfStockCount = ProductStock::where('qty', '<=', 0)->count();

        // Total unique products dengan low stock
        $lowStockProducts = ProductStock::where('qty', '<=', 10)
            ->distinct('product_id')
            ->count('id');

        return [
            Stat::make('Produk Stok Kritis', $lowStockProducts)
                ->description('Produk unique dengan stock rendah')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([7, 8, 6, 9, 10, 12, $lowStockProducts]),

            Stat::make('Total Items Stok Rendah', $lowStockCount)
                ->description('Termasuk semua warehouse')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning')
                ->chart([5, 7, 6, 8, 9, 10, $lowStockCount]),

            Stat::make('Stok Habis', $outOfStockCount)
                ->description('Item dengan qty = 0')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->chart([3, 2, 4, 3, 2, 1, $outOfStockCount]),
        ];
    }

    public static function canView(): bool
    {
        // Tampilkan stats hanya jika ada low stock
        return ProductStock::where('qty', '<=', 10)->exists();
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }
}

