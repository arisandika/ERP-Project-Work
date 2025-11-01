<?php
namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\HR\AttendanceResource;
use App\Filament\Widgets\HR\AttendanceLeaveListWidget;
use App\Filament\Widgets\HR\AttendanceStatsOverview;
use App\Filament\Widgets\HR\AttendanceSummaryOverview;
use Carbon\Carbon;
use Filament\Actions\ExportAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    public function getTabs(): array
    {
        return [
            'all'          => Tab::make('Semua')
                ->icon('heroicon-o-rectangle-stack'),

            'last_month'   => Tab::make('1 Bulan Terakhir')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subMonth()->startOfDay())
                )
                ->badgeColor('primary')
                ->badge(
                    $this->getRecordCount(Carbon::now()->subMonth()->startOfDay())
                ),

            'last_2_weeks' => Tab::make('2 Minggu Terakhir')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subWeeks(2)->startOfDay())
                )
                ->badgeColor('success')
                ->badge(
                    $this->getRecordCount(Carbon::now()->subWeeks(2)->startOfDay())
                ),

            'last_week'    => Tab::make('1 Minggu Terakhir')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subWeek()->startOfDay())
                )
                ->badgeColor('warning')
                ->badge(
                    $this->getRecordCount(Carbon::now()->subWeek()->startOfDay())
                ),

            'today'        => Tab::make('Hari Ini')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->whereDate('date', Carbon::today())
                )
                ->badgeColor('danger')
                ->badge(
                    $this->getRecordCount(Carbon::today())
                ),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }

    protected function getRecordCount($fromDate): int
    {
        return AttendanceResource::getModel()::query()
            ->where('date', '>=', $fromDate)
            ->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(AttendanceExporter::class)
                ->label('Export Presensi')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceSummaryOverview::class,
            AttendanceLeaveListWidget::class,
            AttendanceStatsOverview::class,
        ];
    }
}
