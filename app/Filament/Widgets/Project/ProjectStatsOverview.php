<?php

namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use App\Models\Project\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ProjectStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('project_stats_overview', now()->addMinutes(15), function () {
            $totalTickets = Ticket::count();
            $completedTickets = Ticket::whereHas('ticketStatus', fn ($q) => $q->where('is_completed', true))->count();
            $overdueTickets = Ticket::where('due_date', '<', now())
                ->whereDoesntHave('ticketStatus', fn ($q) => $q->where('is_completed', true))
                ->count();

            return [
                'total_projects' => Project::count(),
                'active_projects' => Project::whereNull('end_date')->orWhere('end_date', '>=', now())->count(),
                'total_tickets' => $totalTickets,
                'completed_tickets' => $completedTickets,
                'overdue_tickets' => $overdueTickets,
                'completion_rate' => $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100) : 0,
            ];
        });

        return [
            Stat::make('Total Projects', number_format($stats['total_projects']))
                ->description("{$stats['active_projects']} aktif")
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary')
                ->chart([3, 5, 4, 6, 5, 7, $stats['total_projects']]),

            Stat::make('Total Tickets', number_format($stats['total_tickets']))
                ->description("{$stats['completed_tickets']} selesai")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->chart([10, 15, 12, 18, 16, 20, $stats['total_tickets']]),

            Stat::make('Completion Rate', "{$stats['completion_rate']}%")
                ->description('Tingkat penyelesaian tiket')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($stats['completion_rate'] >= 70 ? 'success' : 'warning')
                ->chart([$stats['completion_rate'] - 5, $stats['completion_rate'] + 2, $stats['completion_rate'] - 3, $stats['completion_rate']]),

            Stat::make('Overdue Tasks', number_format($stats['overdue_tickets']))
                ->description('Tiket melewati deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stats['overdue_tickets'] > 0 ? 'danger' : 'success'),
        ];
    }
}
