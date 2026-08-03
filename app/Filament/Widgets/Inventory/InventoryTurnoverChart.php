<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryTurnoverChart extends ChartWidget
{
    protected static ?string $heading = 'Inventory Turnover Rate (12 Bulan)';

    protected static ?string $maxHeight = '300px';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $turnoverData = Cache::remember('inventory_turnover_12m', now()->addHours(1), function () {
            $months = collect(range(11, 0))->map(fn ($m) => now()->subMonths($m)->startOfMonth());
            $results = [];

            foreach ($months as $month) {
                $start = $month->copy()->startOfMonth();
                $end = $month->copy()->endOfMonth();

                $cogs = StockTransaction::where('type', 'keluar')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->join('nx_products', 'nx_products.id', '=', 'nx_stock_transactions.product_id')
                    ->sum(DB::raw('nx_stock_transactions.quantity * nx_products.purchase_price'));

                $avgInventory = ProductStock::join('nx_products', 'nx_products.id', '=', 'nx_product_stock.product_id')
                    ->sum(DB::raw('nx_product_stock.qty_available * nx_products.selling_price'));

                $turnover = $avgInventory > 0 ? round(($cogs / $avgInventory) * 100, 1) : 0;
                $results[] = $turnover;
            }

            return $results;
        });

        $labels = collect(range(11, 0))
            ->map(fn ($m) => now()->subMonths($m)->format('M Y'))
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Turnover Rate (%)',
                    'data' => $turnoverData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#8b5cf6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => ['usePointStyle' => true, 'padding' => 20],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(ctx) { return ctx.dataset.label + ": " + ctx.raw + "%"; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => ['callback' => 'function(value) { return value + "%"; }'],
                ],
            ],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }
}
