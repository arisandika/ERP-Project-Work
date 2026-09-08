<?php
namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use App\Filament\Widgets\HR\AttendanceOvertimeSummaryWidget;
use App\Models\HR\Attendance;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAttendanceHistories extends ListRecords
{
    protected static string $resource = AttendanceHistoryResource::class;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses melihat riwayat presensi.');
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceOvertimeSummaryWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return [];
        }

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
            ->forAttendanceReporting()
            ->where('employee_id', $employee->id)
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
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('employee_id', $employee->id)
                )
                ->badge((int) $counts->all_count),

            'last_month' => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereBetween('date', [$startLastMonth, $endLastMonth])
                )
                ->badge((int) $counts->last_month),

            'this_month' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereBetween('date', [$startThisMonth, $endThisMonth])
                )
                ->badge((int) $counts->this_month),

            'last_week' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereBetween('date', [$startWeek, $endWeek])
                )
                ->badge((int) $counts->last_week),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereDate('date', $today)
                )
                ->badge((int) $counts->today),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'this_month';
    }
}
