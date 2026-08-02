<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $productTable = (new Product())->getTable();
        $stockTable = (new ProductStock())->getTable();

        $stockData = Cache::remember('inventory_stats_overview', now()->addMinutes(15), function () use ($productTable, $stockTable) {
            return ProductStock::select(
                DB::raw("COALESCE(SUM({$stockTable}.qty_available), 0) as total_available"),
                DB::raw("COALESCE(SUM({$stockTable}.qty_available * {$productTable}.selling_price), 0) as total_valuation")
            )
            ->join($productTable, "{$stockTable}.product_id", "=", "{$productTable}.id")
            ->first();
        });

        $healthPercentage = Cache::remember('inventory_health_score', now()->addMinutes(15), function () {
            $totalItems = ProductStock::count();
            $lowStockItems = ProductStock::where('qty_available', '<=', 10)->count();
            return $totalItems > 0 ? round((($totalItems - $lowStockItems) / $totalItems) * 100) : 0;
        });

        $movementsCount = Cache::remember('inventory_operational_velocity', now()->addMinutes(15), function () {
            return StockTransaction::whereMonth('transaction_date', now()->month)->count();
        });

        $lowStockCount = Cache::remember('inventory_low_stock_count', now()->addMinutes(15), function () {
            return ProductStock::where('qty_available', '<=', 10)->count();
        });

        return [
            Stat::make('Capital Investment', 'Rp ' . number_format($stockData->total_valuation / 1000000, 2) . 'M')
                ->description('Total valuasi aset inventaris')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([15, 18, 14, 20, 17, 22, round($stockData->total_valuation / 10000000)]),

            Stat::make('Stok Tersedia', number_format($stockData->total_available, 0, ',', '.') . ' unit')
                ->description('Siap didistribusikan')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info')
                ->chart([8, 12, 10, 15, 11, 14, $stockData->total_available]),

            Stat::make('Inventory Health', "{$healthPercentage}%")
                ->description($healthPercentage > 80 ? 'Kondisi stok sehat' : 'Perlu perhatian')
                ->descriptionIcon($healthPercentage > 80 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($healthPercentage > 80 ? 'success' : 'warning')
                ->chart([$healthPercentage, $healthPercentage - 2, $healthPercentage + 1, $healthPercentage - 1, $healthPercentage]),

            Stat::make('Mutasi Bulan Ini', number_format($movementsCount, 0, ',', '.'))
                ->description('Total transaksi stok')
                ->descriptionIcon('heroicon-o-bolt')
                ->color('primary')
                ->chart([5, 8, 6, 10, 7, 9, $movementsCount]),

            Stat::make('Low Stock Alert', $lowStockCount . ' item')
                ->description('Stok di bawah batas minimum')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
