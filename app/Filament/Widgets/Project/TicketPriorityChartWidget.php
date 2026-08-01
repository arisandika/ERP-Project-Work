<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\TicketPriority;
use Filament\Widgets\ChartWidget;

class TicketPriorityChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ticket Berdasarkan Prioritas';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $priorities = TicketPriority::withCount('tickets')->get();

        $colorMap = [
            'High'   => '#ef4444',
            'Medium' => '#f59e0b',
            'Low'    => '#22c55e',
        ];

        return [
            'datasets' => [
                [
                    'data'            => $priorities->pluck('tickets_count')->toArray(),
                    'backgroundColor' => $priorities->map(fn($p) => $colorMap[$p->name] ?? '#9ca3af')->toArray(),
                ],
            ],
            'labels'   => $priorities->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
