<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AttendanceSummaryOverview extends BaseWidget
{
    protected static ?string $pollingInterval  = '30s';
    protected static ?string $maxHeight        = '150px';
    protected int|string|array $columnSpan = '2';
    
    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();

        $attendancesToday = Attendance::whereDate('date', $today)->get();

        $presentCount = $attendancesToday->where('status', 'Hadir')->count();
        $lateCount    = $attendancesToday->where('status', 'Terlambat')->count();

        $absentCount = max(0, $totalEmployees - $attendancesToday->count());

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Semua karyawan terdaftar')
                ->icon('heroicon-o-user-group')
                ->color('gray'),

            Stat::make('Hadir', $presentCount)
                ->description('Presensi tepat waktu hari ini')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Terlambat', $lateCount)
                ->description('Presensi setelah jam kerja')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Belum Presensi', $absentCount)
                ->description('Belum melakukan presensi hari ini')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
