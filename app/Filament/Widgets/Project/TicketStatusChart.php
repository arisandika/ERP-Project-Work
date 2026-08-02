<?php

namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use App\Models\Project\TicketStatus;
use Filament\Widgets\ChartWidget;

class TicketStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Ticket by Status';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $statuses = TicketStatus::withCount('tickets')->orderBy('sort_order')->get();

        return [
            'datasets' => [
                [
                    'data' => $statuses->pluck('tickets_count')->toArray(),
                    'backgroundColor' => $statuses->pluck('color')->toArray(),
                ],
            ],
            'labels' => $statuses->pluck('name')->toArray(),
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
            ],
        ];
    }
}
