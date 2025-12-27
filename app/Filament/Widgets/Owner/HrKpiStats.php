<?php
namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\Sales\Invoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesPerson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrKpiStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = '2';

    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today      = now();
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        return [
            // HR KPI
            Stat::make('Total Karyawan', Employee::count())
                ->description('Jumlah pegawai aktif')
                ->descriptionIcon('heroicon-o-users')
                ->chart(
                    Employee::selectRaw('COUNT(*) as total')
                        ->groupByRaw('MONTH(created_at)')
                        ->pluck('total')
                        ->toArray()
                )
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(
                'Hadir Hari Ini',
                Attendance::whereDate('date', $today)->count()
            )
                ->description('Presensi masuk hari ini')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->chart(
                    Attendance::whereDate('date', '>=', now()->subDays(7))
                        ->selectRaw('COUNT(*) as total')
                        ->groupBy('date')
                        ->pluck('total')
                        ->toArray()
                )
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(
                'Terlambat Hari Ini',
                Attendance::whereDate('date', $today)
                    ->where('status', 'Terlambat')
                    ->count()
            )
                ->description('Datang melewati jam kerja')
                ->descriptionIcon('heroicon-o-clock')
                ->chart(
                    Attendance::where('status', 'Terlambat')
                        ->whereDate('date', '>=', now()->subDays(7))
                        ->selectRaw('COUNT(*) as total')
                        ->groupBy('date')
                        ->pluck('total')
                        ->toArray()
                )
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(
                'Belum Clock Out',
                Attendance::whereDate('date', $today)
                    ->whereNotNull('clock_in')
                    ->whereNull('clock_out')
                    ->count()
            )
                ->description('Masih bekerja')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
