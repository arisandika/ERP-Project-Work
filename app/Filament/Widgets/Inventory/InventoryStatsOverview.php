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
    protected function getStats(): array
    {
        $productTable = (new Product())->getTable();
        $stockTable = (new ProductStock())->getTable();

        // 1. Perbaikan Bug & Implementasi Cache (15 menit)
        $stockData = Cache::remember('inventory_stats_overview', now()->addMinutes(15), function () use ($productTable, $stockTable) {
            return ProductStock::select(
                DB::raw("COALESCE(SUM({$stockTable}.qty_available), 0) as total_available"),
                DB::raw("COALESCE(SUM({$stockTable}.qty_available * {$productTable}.selling_price), 0) as total_valuation")
            )
            ->join($productTable, "{$stockTable}.product_id", "=", "{$productTable}.id")
            ->first();
        });

        // 2. Health Index (Di-cache)
        $healthPercentage = Cache::remember('inventory_health_score', now()->addMinutes(15), function () {
            $totalItems = ProductStock::count();
            $lowStockItems = ProductStock::where('qty_available', '<=', 10)->count();
            return $totalItems > 0 ? round((($totalItems - $lowStockItems) / $totalItems) * 100) : 0;
        });

        // 3. Operational Velocity (Di-cache)
        $movementsCount = Cache::remember('inventory_operational_velocity', now()->addMinutes(15), function () {
            return StockTransaction::whereMonth('transaction_date', now()->month)->count();
        });

        return [
            Stat::make('Capital Investment', 'IDR ' . number_format($stockData->total_valuation / 1000000, 2) . 'M')
                ->description('Total valuasi aset inventaris')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Stock Siap Jual (Available)', number_format($stockData->total_available, 0, ',', '.'))
                ->description('Total unit siap didistribusikan')
                ->icon('heroicon-o-shopping-cart')
                ->color('info'),

            Stat::make('Inventory Health Score', "{$healthPercentage}%")
                ->icon($healthPercentage > 80 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($healthPercentage > 80 ? 'success' : 'warning'),

            Stat::make('Operational Velocity', number_format($movementsCount, 0, ',', '.'))
                ->description('Total mutasi stok bulan ini')
                ->icon('heroicon-o-bolt')
                ->color('primary'),
        ];
    }
}
