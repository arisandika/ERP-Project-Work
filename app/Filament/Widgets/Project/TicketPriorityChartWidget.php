<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Widgets\ChartWidget;

class TicketPriorityChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ticket Berdasarkan Prioritas';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 3,
    ];

    protected function getData(): array
    {
        $grouped = Ticket::query()
            ->leftJoin('nx_ticket_priorities', 'nx_tickets.priority_id', '=', 'nx_ticket_priorities.id')
            ->selectRaw("COALESCE(nx_ticket_priorities.name, 'No Priority') as priority_name, count(*) as total")
            ->groupBy('priority_name')
            ->pluck('total', 'priority_name');

        $colorMap = [
            'High' => '#EF4444',
            'Medium' => '#F59E0B',
            'Low' => '#22C55E',
            'No Priority' => '#9CA3AF',
        ];

        $labels = $grouped->keys()->toArray();
        $data = $grouped->values()->toArray();
        $colors = array_map(fn($label) => $colorMap[$label] ?? '#6B7280', $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Ticket',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
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
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
