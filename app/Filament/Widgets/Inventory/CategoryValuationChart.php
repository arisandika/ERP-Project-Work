<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryValuationChart extends ChartWidget
{
    protected static ?string $heading = 'Inventory Valuation by Category';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $data = Category::with(['products.productStocks'])
            ->get()
            ->map(function ($category) {
                $valuation = $category->products->sum(function ($product) {
                    return $product->productStocks->sum('qty_available') * $product->selling_price;
                });
                return [
                    'label' => $category->name,
                    'value' => $valuation,
                ];
            })
            ->sortByDesc('value')
            ->take(8);

        return [
            'datasets' => [
                [
                    'label' => 'Valuasi',
                    'data' => $data->pluck('value')->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
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
                        'label' => 'function(ctx) { return "Rp " + ctx.raw.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + (value / 1000000).toFixed(0) + "M"; }',
                    ],
                ],
            ],
        ];
    }
}
