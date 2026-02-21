<?php
namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\HR\AttendanceResource;
use App\Filament\Widgets\HR\AttendanceLeaveListWidget;
use App\Filament\Widgets\HR\AttendanceMapOverview;
use App\Filament\Widgets\HR\AttendanceSummaryOverview;
use App\Models\HR\Attendance;
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
        $now = now();

        $start3Months = $now->copy()->subMonths(3)->startOfDay();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth = $now->copy()->endOfMonth();
        $startWeek = $now->copy()->startOfWeek();
        $endWeek = $now->copy()->endOfWeek();
        $today = $now->toDateString();

        $counts = Attendance::query()
            ->selectRaw("
            COUNT(*) as all_count,
            COALESCE(SUM(date >= ?),0) as last_3_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as last_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as this_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as last_week,
            COALESCE(SUM(DATE(date) = ?),0) as today
        ", [
                $start3Months,
                $startLastMonth,
                $endLastMonth,
                $startThisMonth,
                $endThisMonth,
                $startWeek,
                $endWeek,
                $today
            ])
            ->first();

        return [

            'all' => Tab::make('Semua')
                ->badge((int) $counts->all_count),

            'last_3_month' => Tab::make('3 Bulan Terakhir')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('date', '>=', $start3Months)
                )
                ->badge((int) $counts->last_3_month),

            'last_month' => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startLastMonth, $endLastMonth])
                )
                ->badge((int) $counts->last_month),

            'this_month' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startThisMonth, $endThisMonth])
                )
                ->badge((int) $counts->this_month),

            'last_week' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startWeek, $endWeek])
                )
                ->badge((int) $counts->last_week),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereDate('date', $today)
                )
                ->badge((int) $counts->today),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
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
