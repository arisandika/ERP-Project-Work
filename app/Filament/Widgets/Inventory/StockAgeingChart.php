<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class StockAgeingChart extends ChartWidget
{
    protected static ?string $heading = 'Stock Ageing Profile';

    protected static ?string $maxHeight = '300px';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $ageingData = Cache::remember('stock_ageing_profile', now()->addMinutes(15), function () {
            $products = Product::query()
                ->join('nx_product_stock', 'nx_product_stock.product_id', '=', 'nx_products.id')
                ->selectRaw('nx_products.id, SUM(nx_product_stock.qty_available) as total_stock')
                ->where('nx_product_stock.qty_available', '>', 0)
                ->groupBy('nx_products.id')
                ->get();

            $ageing = ['0-30 hari' => 0, '31-60 hari' => 0, '61-90 hari' => 0, '90+ hari' => 0];

            foreach ($products as $product) {
                $lastMovement = StockTransaction::where('product_id', $product->id)
                    ->latest('transaction_date')
                    ->value('transaction_date');

                if (! $lastMovement) {
                    $ageing['90+ hari'] += $product->total_stock;
                    continue;
                }

                $daysSince = now()->diffInDays($lastMovement);
                match (true) {
                    $daysSince <= 30 => $ageing['0-30 hari'] += $product->total_stock,
                    $daysSince <= 60 => $ageing['31-60 hari'] += $product->total_stock,
                    $daysSince <= 90 => $ageing['61-90 hari'] += $product->total_stock,
                    default => $ageing['90+ hari'] += $product->total_stock,
                };
            }

            return $ageing;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Unit',
                    'data' => array_values($ageingData),
                    'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($ageingData),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(ctx) { return ctx.raw.toLocaleString("id-ID") + " unit"; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}
