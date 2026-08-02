<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryValuationChart extends ChartWidget
{
    protected static ?string $heading = 'Valuasi per Kategori';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
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
            })->sortByDesc('value')->take(5);

        return [
            'datasets' => [
                [
                    'label' => 'Valuasi',
                    'data' => $data->pluck('value')->toArray(),
                    'backgroundColor' => ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'polarArea';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(ctx) { return "Rp " + ctx.raw.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'scales' => [
                'r' => [
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                ],
            ],
        ];
    }
}
