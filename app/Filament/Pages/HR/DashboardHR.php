<?php
namespace App\Filament\Pages\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\HR\AbsentTodayTableWidget;
use App\Filament\Widgets\HR\AttendanceTodayChartWidget;
use App\Filament\Widgets\HR\AttendanceTrendChartWidget;
use App\Filament\Widgets\HR\EmployeeByDepartmentChartWidget;
use App\Filament\Widgets\HR\EmployeeStatusChartWidget;
use App\Filament\Widgets\HR\HrOverviewWidget;
use App\Filament\Widgets\HR\PendingLeaveApprovalsTableWidget;
use App\Filament\Widgets\HR\UpcomingHolidaysWidget;
use Filament\Pages\Page;

class DashboardHR extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'hr';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.hr.dashboard-hr';

    protected static ?string $slug = 'hr/dashboard';

    protected static string $routePath = 'hr/dashboard';

    protected static ?string $navigationLabel = 'Dashboard HR';

    protected static ?string $title = 'Dashboard HR';

    protected function getHeaderWidgets(): array
    {
        return [
            HrOverviewWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            AttendanceTodayChartWidget::class,
            EmployeeByDepartmentChartWidget::class,
            EmployeeStatusChartWidget::class,
            AttendanceTrendChartWidget::class,
            PendingLeaveApprovalsTableWidget::class,
            AbsentTodayTableWidget::class,
            UpcomingHolidaysWidget::class,
        ];
    }

    protected function getColumns(): int|array
    {
        return [
            'default' => 12,
            'md' => 4,
        ];
    }
}
