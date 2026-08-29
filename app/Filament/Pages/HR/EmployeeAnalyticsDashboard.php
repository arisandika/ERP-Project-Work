<?php

namespace App\Filament\Pages\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\EmployeeAnalytics\Attendance\ClockInOutAverageWidget;
use App\Filament\Widgets\EmployeeAnalytics\Sections\AnalyticsSectionHeaderWidget;
use App\Filament\Widgets\EmployeeAnalytics\Attendance\AttendanceHeatmapChart;
use App\Filament\Widgets\EmployeeAnalytics\Attendance\AttendanceStatusDonutChart;
use App\Filament\Widgets\EmployeeAnalytics\Attendance\AttendanceTrendChart;
use App\Filament\Widgets\EmployeeAnalytics\Attendance\WorkHoursChart;
use App\Filament\Widgets\EmployeeAnalytics\Leave\LeaveBalanceRadialChart;
use App\Filament\Widgets\EmployeeAnalytics\Leave\LeaveHistoryTable;
use App\Filament\Widgets\EmployeeAnalytics\Leave\LeaveTrendChart;
use App\Filament\Widgets\EmployeeAnalytics\Overview\EmployeeOverviewStats;
use App\Filament\Widgets\EmployeeAnalytics\Reimbursement\MonthlyReimbursementChart;
use App\Filament\Widgets\EmployeeAnalytics\Reimbursement\RecentReimbursementTable;
use App\Filament\Widgets\EmployeeAnalytics\Reimbursement\ReimbursementStatusChart;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;

class EmployeeAnalyticsDashboard extends Page
{
    use HasFiltersForm;

    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'attendance';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Employee Analytics';

    protected static ?string $title = 'Employee Analytics';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.hr.employee-analytics-dashboard';

    protected static ?string $slug = '/employee-analytics';

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation()
            && static::moduleShouldRegisterNavigation();
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Analytics')
                    ->description('Filter seluruh data analytics berdasarkan periode tertentu.')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Tanggal Awal')
                            ->default(now()->startOfMonth())
                            ->native(false)
                            ->live(),

                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth())
                            ->native(false)
                            ->live(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeOverviewStats::class,
        ];
    }

    public function getVisibleWidgets(): array
    {
        return [
            // ── Attendance Section ──
            AnalyticsSectionHeaderWidget::make([
                'title' => 'Attendance Analytics',
                'description' => 'Monitor aktivitas kehadiran, keterlambatan, dan jam kerja.',
                'icon' => 'heroicon-o-calendar-days',
            ]),

            AttendanceTrendChart::class,
            AttendanceStatusDonutChart::class,
            // WorkHoursChart::class,
            AttendanceHeatmapChart::class,
            ClockInOutAverageWidget::class,

            // ── Leave Section ──
            AnalyticsSectionHeaderWidget::make([
                'title' => 'Leave Analytics',
                'description' => 'Pantau penggunaan cuti dan riwayat pengajuan.',
                'icon' => 'heroicon-o-clock',
            ]),

            LeaveBalanceRadialChart::class,
            LeaveTrendChart::class,
            LeaveHistoryTable::class,

            // ── Reimbursement Section ──
            AnalyticsSectionHeaderWidget::make([
                'title' => 'Reimbursement Analytics',
                'description' => 'Monitor pengajuan reimburse dan status approval.',
                'icon' => 'heroicon-o-banknotes',
            ]),

            ReimbursementStatusChart::class,
            MonthlyReimbursementChart::class,
            RecentReimbursementTable::class,
        ];
    }
}
