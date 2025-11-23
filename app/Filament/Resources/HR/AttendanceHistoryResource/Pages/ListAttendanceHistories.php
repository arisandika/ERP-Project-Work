<?php
namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use App\Models\HR\Attendance;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAttendanceHistories extends ListRecords
{
    protected static string $resource = AttendanceHistoryResource::class;

    public function getTabs(): array
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return [];
        }

        $baseQuery = Attendance::where('employee_id', $employee->id);

        return [
            'all' => Tab::make('Semua')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                ),

            'last_3_month' => Tab::make('3 Bulan Terakhir')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->where('date', '>=', now()->subMonths(3)->startOfDay())
                )
                ->badge(
                    (clone $baseQuery)
                        ->where('date', '>=', now()->subMonths(3)->startOfDay())
                        ->count()
                ),

            'last_month' => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereMonth('date', now()->subMonth()->month)
                        ->whereYear('date', now()->subMonth()->year)
                )
                ->badge(
                    (clone $baseQuery)
                        ->whereMonth('date', now()->subMonth()->month)
                        ->whereYear('date', now()->subMonth()->year)
                        ->count()
                ),

            'this_month' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereMonth('date', now()->month)
                        ->whereYear('date', now()->year)
                )
                ->badge(
                    (clone $baseQuery)
                        ->whereMonth('date', now()->month)
                        ->whereYear('date', now()->year)
                        ->count()
                ),

            'last_week' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereBetween('date', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                )
                ->badge(
                    (clone $baseQuery)
                        ->whereBetween('date', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                        ->count()
                ),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->whereDate('date', now())
                )
                ->badge(
                    (clone $baseQuery)
                        ->whereDate('date', now())
                        ->count()
                ),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'this_month';
    }
}
