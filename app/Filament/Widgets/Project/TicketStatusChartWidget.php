<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\TicketStatus;
use Filament\Widgets\ChartWidget;

class TicketStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Ticket';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $statuses = TicketStatus::withCount('tickets')
            ->having('tickets_count', '>', 0)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Ticket',
                    'data'            => $statuses->pluck('tickets_count')->toArray(),
                    'backgroundColor' => $statuses->pluck('color')->toArray(),
                ],
            ],
            'labels'   => $statuses->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
