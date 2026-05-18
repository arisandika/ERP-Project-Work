<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryValuationChart extends ChartWidget
{
    protected static ?string $heading = 'Valuasi Inventaris per Kategori (IDR)';
    protected static ?string $maxHeight = '300px';

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
        return 'polarArea'; // Memberikan kesan data analis yang modern
    }
}
