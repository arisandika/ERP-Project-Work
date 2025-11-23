<?php
namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\HR\AttendanceResource;
use App\Filament\Widgets\HR\AttendanceLeaveListWidget;
use App\Filament\Widgets\HR\AttendanceMapOverview;
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
            'all'              => Tab::make('Semua'),

            'last_3_month' => Tab::make('3 Bulan Terakhir')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subMonths(3)->startOfDay())
                )
                ->badge(
                    $this->getRecordCount(Carbon::now()->subMonths(3)->startOfDay())
                ),

            'last_month'       => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subMonth()->startOfDay())
                )
                ->badge(
                    $this->getRecordCount(Carbon::now()->subMonth()->startOfDay())
                ),

            'this_month'       => Tab::make('Bulan Ini')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->startOfMonth())
                )
                ->badge(
                    $this->getRecordCount(Carbon::now()->startOfMonth())
                ),

            'last_week'        => Tab::make('Minggu Ini')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('date', '>=', Carbon::now()->subWeek()->startOfDay())
                )
                ->badge(
                    $this->getRecordCount(Carbon::now()->subWeek()->startOfDay())
                ),

            'today'            => Tab::make('Hari Ini')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->whereDate('date', Carbon::today())
                )
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
            // AttendanceStatusChart::class,
            AttendanceMapOverview::class,
        ];
    }
}
