<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use App\Models\Project\TicketStatus;
use Filament\Widgets\ChartWidget;

class TicketStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Ticket Berdasarkan Status';

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 122,
        'xl' => 12,
    ];

    protected function getData(): array
    {
        $statuses = TicketStatus::withCount('tickets')
            ->orderBy('sort_order')
            ->get();

        // fallback kalau TicketStatus tidak punya sort_order global,
        // group manual berdasarkan nama status di semua project
        $grouped = Ticket::query()
            ->join('nx_ticket_statuses', 'nx_tickets.ticket_status_id', '=', 'nx_ticket_statuses.id')
            ->selectRaw('nx_ticket_statuses.name as status_name, count(*) as total')
            ->groupBy('nx_ticket_statuses.name')
            ->pluck('total', 'status_name');

        $colorMap = [
            'Backlog' => '#9CA3AF',
            'To Do' => '#F59E0B',
            'In Progress' => '#3B82F6',
            'Review' => '#8B5CF6',
            'Done' => '#22C55E',
        ];

        $labels = $grouped->keys()->toArray();
        $data = $grouped->values()->toArray();
        $colors = array_map(fn($label) => $colorMap[$label] ?? '#6B7280', $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Ticket',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
