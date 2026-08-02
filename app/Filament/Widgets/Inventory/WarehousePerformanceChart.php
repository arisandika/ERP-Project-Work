<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Warehouse;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class WarehousePerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Warehouse Performance';

    protected static ?string $maxHeight = '300px';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $data = Cache::remember('warehouse_performance', now()->addMinutes(15), fn () =>
            Warehouse::where('is_active', true)
                ->withSum('stocks', 'qty_available')
                ->withSum('stocks', 'qty_reserved')
                ->withCount('stocks')
                ->get()
                ->map(function ($warehouse) {
                    $totalUnits = $warehouse->stocks_sum_qty_available + $warehouse->stocks_sum_qty_reserved;
                    return [
                        'name' => $warehouse->warehouse_name,
                        'available' => (int) $warehouse->stocks_sum_qty_available,
                        'reserved' => (int) $warehouse->stocks_sum_qty_reserved,
                    ];
                })
                ->sortByDesc('available')
                ->values()
        );

        return [
            'datasets' => [
                [
                    'label' => 'Available',
                    'data' => $data->pluck('available')->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderWidth' => 0,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Reserved',
                    'data' => $data->pluck('reserved')->toArray(),
                    'backgroundColor' => '#f59e0b',
                    'borderWidth' => 0,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => ['usePointStyle' => true, 'padding' => 15],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'callbacks' => [
                        'label' => 'function(ctx) { return ctx.dataset.label + ": " + ctx.raw.toLocaleString("id-ID") + " unit"; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString("id-ID"); }',
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
