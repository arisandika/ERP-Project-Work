<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use App\Models\Project\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProjects  = Project::count();
        $activeProjects = Project::whereDate('end_date', '>=', now())->count();

        $totalTickets = Ticket::count();
        $doneTickets  = Ticket::whereHas('status', fn($q) => $q->where('is_completed', true))->count();

        $overdueTickets = Ticket::whereDate('due_date', '<', now())
            ->whereHas('status', fn($q) => $q->where('is_completed', false))
            ->count();

        $completionRate = $totalTickets > 0
            ? round(($doneTickets / $totalTickets) * 100, 1)
            : 0;

        return [
            Stat::make('Total Project', $totalProjects)
                ->description("{$activeProjects} project berjalan")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->url(route('filament.admin.resources.pm.projects.index')),

            Stat::make('Total Ticket', $totalTickets)
                ->description("{$doneTickets} selesai")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info')
                ->url(route('filament.admin.resources.pm.tickets.index')),

            Stat::make('Ticket Terlambat', $overdueTickets)
                ->description($overdueTickets > 0 ? 'Perlu segera ditindaklanjuti' : 'Semua on-track')
                ->descriptionIcon($overdueTickets > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueTickets > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.pm.tickets.index')),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description('Rasio ticket selesai keseluruhan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($completionRate >= 75 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger'))
                ->chart($this->getWeeklyCompletionTrend()),
        ];
    }

    protected function getWeeklyCompletionTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = now()->subDays($i);
            $data[] = Ticket::whereHas('status', fn($q) => $q->where('is_completed', true))
                ->whereDate('updated_at', $date)
                ->count();
        }
        return $data;
    }
}
