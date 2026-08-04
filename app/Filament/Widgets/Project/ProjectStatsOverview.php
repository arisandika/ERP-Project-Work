<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use App\Models\Project\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected static ?int $sort                = 1;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalProjects   = Project::count();
        $ongoingProjects = Project::whereDate('end_date', '>=', now())->count();
        $overdueProjects = Project::whereDate('end_date', '<', now())
            ->whereDoesntHave('tickets', function ($query) {
                $query->whereHas('status', fn($q) => $q->where('name', '!=', 'Done'));
            }, '=', 0)
            ->count();

        $totalTickets   = Ticket::count();
        $doneTickets    = Ticket::whereHas('status', fn($q) => $q->where('name', 'Done'))->count();
        $overdueTickets = Ticket::whereDate('due_date', '<', now())
            ->whereHas('status', fn($q) => $q->where('name', '!=', 'Done'))
            ->count();

        return [
            Stat::make('Total Project', $totalProjects)
                ->description('Jumlah seluruh project')
                ->descriptionIcon('heroicon-o-square-3-stack-3d')
                ->color('primary'),

            Stat::make('Project Berjalan', $ongoingProjects)
                ->description('Project dengan deadline belum lewat')
                ->descriptionIcon('heroicon-o-play-circle')
                ->color('info'),

            Stat::make('Total Ticket', $totalTickets)
                ->description($doneTickets . ' selesai dari ' . $totalTickets)
                ->descriptionIcon('heroicon-o-ticket')
                ->color('success'),

            Stat::make('Ticket Terlambat', $overdueTickets)
                ->description('Melewati due date & belum Done')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overdueTickets > 0 ? 'danger' : 'success'),
        ];
    }
}
