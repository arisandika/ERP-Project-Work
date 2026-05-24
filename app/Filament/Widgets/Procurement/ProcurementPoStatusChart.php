<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class ProcurementPoStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'PO Status Distribution';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 4,
    ];

    protected function getData(): array
    {
        $statusCounts = PurchaseOrder::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'data' => array_values($statusCounts),

                    'backgroundColor' => [
                        '#9ca3af',
                        '#3b82f6',
                        '#f59e0b',
                        '#10b981',
                        '#ef4444',
                    ],
                ],
            ],

            'labels' => array_map(
                fn ($label) => ucfirst($label),
                array_keys($statusCounts)
            ),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '72%',

            'plugins' => [
                'legend' => [
                    'position' => 'bottom',

                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 18,
                    ],
                ],
            ],
        ];
    }
}
