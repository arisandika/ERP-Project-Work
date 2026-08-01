<?php
namespace App\Filament\Pages\Project;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Project\MyTasksTableWidget;
use App\Filament\Widgets\Project\OverdueTicketsTableWidget;
use App\Filament\Widgets\Project\ProjectOverviewWidget;
use App\Filament\Widgets\Project\ProjectProgressChartWidget;
use App\Filament\Widgets\Project\TicketPriorityChartWidget;
use App\Filament\Widgets\Project\TicketStatusChartWidget;
use App\Filament\Widgets\Project\UpcomingDeadlinesWidget;
use Filament\Pages\Page;

class DashboardProject extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'project';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.project.dashboard-project';

    protected static ?string $slug = 'pm/dashboard';

    protected static string $routePath = 'pm/dashboard';

    protected static ?string $navigationLabel = 'Dashboard Project';

    protected static ?string $title = 'Dashboard Project';

    protected function getHeaderWidgets(): array
    {
        return [
            ProjectOverviewWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            TicketStatusChartWidget::class,
            ProjectProgressChartWidget::class,
            TicketPriorityChartWidget::class,
            MyTasksTableWidget::class,
            OverdueTicketsTableWidget::class,
            UpcomingDeadlinesWidget::class,
        ];
    }

    protected function getColumns(): int | array
    {
        return 4;
    }
}
