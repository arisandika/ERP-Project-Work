<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Warehouse;
use Filament\Widgets\ChartWidget;

class StockWarehouseChart extends ChartWidget
{
    protected static ?string $heading = 'Sebaran Stok per Gudang';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $warehouses = Warehouse::withSum('stocks', 'qty_available')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Stok Tersedia',
                    'data' => $warehouses->pluck('stocks_sum_qty_available')->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $warehouses->pluck('warehouse_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '65%',
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
                        'label' => 'function(ctx) { return ctx.label + ": " + ctx.raw.toLocaleString("id-ID") + " unit"; }',
                    ],
                ],
            ],
        ];
    }
}
