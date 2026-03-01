<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventorySummaryOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalProducts = Product::count();

        // Asumsi enterprise: Kita hanya menghitung gudang yang aktif beroperasi
        $totalWarehouses = Warehouse::where('is_active', true)->count();

        // REVISI ARSITEKTUR: Mengambil sum dari 3 kolom sekaligus dalam 1 query agar lebih efisien (O(1) Query)
        $stockData = ProductStock::select(
            DB::raw('COALESCE(SUM(qty_available), 0) as total_available'),
            DB::raw('COALESCE(SUM(qty_reserved), 0) as total_reserved'),
            DB::raw('COALESCE(SUM(qty_on_delivery), 0) as total_delivery')
        )->first();

        // Kalkulasi untuk Widget
        $totalAvailable = (int) $stockData->total_available;
        $totalPhysical  = $totalAvailable + (int) $stockData->total_reserved + (int) $stockData->total_delivery;

        return [
            Stat::make('Total Product', number_format($totalProducts, 0, ',', '.'))
                ->description('Total SKU terdaftar')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Total Gudang', number_format($totalWarehouses, 0, ',', '.'))
                ->description('Warehouse aktif beroperasi')
                ->icon('heroicon-o-building-storefront')
                ->color('info'),

            // Kartu untuk Tim Sales (Hanya yang bisa dijual)
            Stat::make('Stock Siap Jual', number_format($totalAvailable, 0, ',', '.'))
                ->description('Total unit (Available)')
                ->icon('heroicon-o-shopping-cart')
                ->color('success'),

            // Kartu untuk Tim Gudang & Audit (Total Fisik/Aset)
            Stat::make('Total Fisik Keseluruhan', number_format($totalPhysical, 0, ',', '.'))
                ->description('Tersedia + Dipesan + Pengiriman')
                ->icon('heroicon-o-archive-box')
                ->color('gray'),
        ];
    }
}
