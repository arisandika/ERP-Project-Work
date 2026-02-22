<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AttendanceSummaryOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?string $maxHeight = '150px';

    protected int|string|array $columnSpan = '2';

    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        // Total karyawan
        $totalEmployees = Employee::where('status', 'Aktif')->count();

        // Hitung semua status presensi langsung di query
        $attendanceStats = Attendance::whereDate('date', $today)
            ->selectRaw("
            COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as present_count,
            COUNT(CASE WHEN status = 'Terlambat' THEN 1 END) as late_count
        ")
            ->first();

        // Hitung belum presensi
        $absentCount = max(0, $totalEmployees - ($attendanceStats->present_count + $attendanceStats->late_count));

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Semua karyawan aktif')
                ->icon('heroicon-o-user-group')
                ->color('gray'),

            Stat::make('Hadir', $attendanceStats->present_count)
                ->description('Presensi tepat waktu hari ini')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Terlambat', $attendanceStats->late_count)
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
