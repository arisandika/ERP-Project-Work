<?php

namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Widgets\ChartWidget;

class DealPipelineChart extends ChartWidget
{
    protected static ?string $heading = 'Deal Pipeline';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 1,
        'xl' => 12,
    ];

    protected function getData(): array
    {
        $stages = DealStage::withCount('deals')->orderBy('sort_order')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Deals',
                    'data' => $stages->pluck('deals_count')->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                ],
            ],
            'labels' => $stages->pluck('name')->toArray(),
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
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
