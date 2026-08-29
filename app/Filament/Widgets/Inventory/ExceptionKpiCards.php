<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;

class ExceptionKpiCards extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected function getColumns(): int
    {
        return [
            'default' => 12,
            'md' => 4,
        ];
    }

    protected function getStats(): array
    {
        $cacheTtl = now()->addMinutes(5);

        $outOfStock = Cache::remember('mon_out_of_stock', $cacheTtl, fn() =>
            ProductStock::where('qty_available', '<=', 0)
                ->where('qty_reserved', '<=', 0)
                ->distinct('product_id')
                ->count('product_id'));

        $lowStock = Cache::remember('mon_low_stock', $cacheTtl, fn() =>
            Product::whereHas('productStocks', fn($q) =>
                $q
                    ->whereColumn('qty_available', '<=', 'nx_products.min_stock')
                    ->where('qty_available', '>', 0))->count());

        $negativeStock = Cache::remember('mon_negative', $cacheTtl, fn() =>
            ProductStock::where('qty_available', '<', 0)->count());

        $overstock = Cache::remember('mon_overstock', $cacheTtl, fn() =>
            Product::where('max_stock', '>', 0)
                ->whereHas('productStocks', fn($q) =>
                    $q
                        ->join('nx_products', 'nx_products.id', '=', 'nx_product_stock.product_id')
                        ->whereColumn('nx_product_stock.qty_available', '>', 'nx_products.max_stock'))
                ->count());

        $nearExpiry = Cache::remember('mon_near_expiry', $cacheTtl, fn() =>
            \App\Models\Inventory\SerialNumber::where('status', 'AVAILABLE')
                ->where('warranty_expired_at', '>=', now())
                ->where('warranty_expired_at', '<=', now()->addDays(30))
                ->count());

        $pendingTransfers = Cache::remember('mon_pending_transfers', $cacheTtl, fn() =>
            StockTransaction::where('mutation_type', 'stock_in')
                ->whereDate('transaction_date', now())
                ->count());

        $failedTransactions = Cache::remember('mon_failed_tx', $cacheTtl, fn() =>
            StockTransaction::where('type', 'keluar')
                ->whereDate('transaction_date', '>=', now()->subDays(1))
                ->where('stock_after', '<', 0)
                ->count());

        $discrepancies = Cache::remember('mon_discrepancies', $cacheTtl, fn() =>
            ProductStock::where('qty_available', '<', 0)
                ->orWhere('qty_reserved', '<', 0)
                ->orWhere('qty_on_delivery', '<', 0)
                ->count());

        return [
            Stat::make('Out of Stock', $outOfStock)
                ->description('Produk habis stok')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
            Stat::make('Low Stock', $lowStock)
                ->description('Di bawah minimum')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Negative Stock', $negativeStock)
                ->description('Integritas data')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($negativeStock > 0 ? 'danger' : 'success'),
            Stat::make('Overstock', $overstock)
                ->description('Melebihi batas max')
                ->descriptionIcon('heroicon-o-archive-box-arrow-down')
                ->color($overstock > 0 ? 'warning' : 'success'),
            Stat::make('Near Expiry', $nearExpiry)
                ->description('Expire ≤ 30 hari')
                ->descriptionIcon('heroicon-o-clock')
                ->color($nearExpiry > 0 ? 'warning' : 'success'),
            Stat::make('Pending Transfers', $pendingTransfers)
                ->description('Menunggu diproses')
                ->descriptionIcon('heroicon-o-arrows-right-left')
                ->color($pendingTransfers > 0 ? 'info' : 'success'),
            Stat::make('Failed Transactions', $failedTransactions)
                ->description('Perlu investigasi')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($failedTransactions > 0 ? 'danger' : 'success'),
            Stat::make('Discrepancies', $discrepancies)
                ->description('Stok tidak normal')
                ->descriptionIcon('heroicon-o-question-mark-circle')
                ->color($discrepancies > 0 ? 'warning' : 'success'),
        ];
    }
}
