<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalEmployees = Employee::forAttendanceReporting()
            ->where('status', 'active')
            ->count();

        $todayAttendance = Attendance::forAttendanceReporting()
            ->whereDate('date', now())
            ->get();
        $hadirToday      = $todayAttendance->whereIn('status', ['hadir', 'terlambat'])->count();
        $terlambatToday  = $todayAttendance->where('status', 'terlambat')->count();
        $absenToday      = $totalEmployees - $todayAttendance->count();

        $pendingLeaves = LeaveRequest::where('status', 'pending')
            ->whereDoesntHave(
                'employee.user.roles',
                fn ($query) => $query->where('name', 'super_admin')
            )
            ->count();

        $attendanceRate = $totalEmployees > 0
            ? round(($hadirToday / $totalEmployees) * 100, 1)
            : 0;

        return [
            Stat::make('Total Karyawan Aktif', $totalEmployees)
                ->description('Karyawan berstatus active')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Hadir Hari Ini', $hadirToday . ' / ' . $totalEmployees)
                ->description($attendanceRate . '% attendance rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger'))
                ->chart($this->getWeeklyAttendanceTrend()),

            Stat::make('Terlambat Hari Ini', $terlambatToday)
                ->description($terlambatToday > 0 ? 'Perlu perhatian' : 'Tidak ada keterlambatan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($terlambatToday > 0 ? 'warning' : 'success'),

            Stat::make('Cuti Menunggu Approval', $pendingLeaves)
                ->description($pendingLeaves > 0 ? 'Perlu segera direview' : 'Semua sudah diproses')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingLeaves > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getWeeklyAttendanceTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = now()->subDays($i);
            $data[] = Attendance::forAttendanceReporting()
                ->whereDate('date', $date)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->count();
        }
        return $data;
    }
}
