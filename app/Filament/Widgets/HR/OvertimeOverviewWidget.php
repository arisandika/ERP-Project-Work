<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;

class OvertimeOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];

    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Ambil presensi hari ini dengan clock_out dan shift
        $todayAttendances = Attendance::whereDate('date', $today)
            ->whereNotNull('clock_out')
            ->whereNotNull('shift_id')
            ->with(['shift', 'employee'])
            ->get();

        $todayOvertimeMinutes = 0;
        $todayOvertimeCount = 0;
        foreach ($todayAttendances as $att) {
            $ot = $att->overtime_minutes;
            if ($ot > 0) {
                $todayOvertimeMinutes += $ot;
                $todayOvertimeCount++;
            }
        }

        // Ambil presensi bulan ini
        $monthAttendances = Attendance::whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereNotNull('clock_out')
            ->whereNotNull('shift_id')
            ->with(['shift', 'employee'])
            ->get();

        $monthOvertimeMinutes = 0;
        $monthOvertimeCount = 0;
        $employeeOvertimeMap = [];

        foreach ($monthAttendances as $att) {
            $ot = $att->overtime_minutes;
            if ($ot > 0) {
                $monthOvertimeMinutes += $ot;
                $monthOvertimeCount++;
                $eid = $att->employee_id;
                $employeeOvertimeMap[$eid] = ($employeeOvertimeMap[$eid] ?? 0) + $ot;
            }
        }

        // Karyawan dengan lembur terbanyak bulan ini
        $topEmployeeId = null;
        $topEmployeeMin = 0;
        $topEmployeeName = '—';
        if (!empty($employeeOvertimeMap)) {
            arsort($employeeOvertimeMap);
            $topEmployeeId = array_key_first($employeeOvertimeMap);
            $topEmployeeMin = $employeeOvertimeMap[$topEmployeeId] ?? 0;
            $emp = Employee::find($topEmployeeId);
            $topEmployeeName = $emp?->full_name ?? '—';
        }

        // Rata-rata lembur per karyawan bulan ini
        $uniqueEmployeeCount = count(array_unique(
            $monthAttendances->where(fn($a) => $a->overtime_minutes > 0)->pluck('employee_id')->toArray()
        ));
        $avgPerEmployee = $uniqueEmployeeCount > 0
            ? intdiv($monthOvertimeMinutes, $uniqueEmployeeCount)
            : 0;

        // Format helpers
        $fmtMin = function (int $minutes): string {
            if ($minutes <= 0)
                return '0 jam';
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;
            if ($h > 0 && $m > 0)
                return "{$h} jam {$m} menit";
            if ($h > 0)
                return "{$h} jam";
            return "{$m} menit";
        };

        $topOtLabel = $topEmployeeMin > 0
            ? ($fmtMin($topEmployeeMin) . ' — ' . $topEmployeeName)
            : '—';

        return [
            Stat::make('Lembur Hari Ini', $fmtMin($todayOvertimeMinutes))
                ->description("{$todayOvertimeCount} karyawan lembur hari ini")
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayOvertimeMinutes > 0 ? 'warning' : 'gray'),
            Stat::make('Total Lembur Bulan Ini', $fmtMin($monthOvertimeMinutes))
                ->description("{$monthOvertimeCount} sesi dari " . Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($monthOvertimeMinutes > 0 ? 'info' : 'gray'),
            Stat::make('Rata-rata / Karyawan', $fmtMin($avgPerEmployee))
                ->description("{$uniqueEmployeeCount} karyawan punya lembur bulan ini")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($avgPerEmployee > 0 ? 'primary' : 'gray'),
            Stat::make('Lembur Terbanyak', $topOtLabel)
                ->description('Karyawan dengan lembur tertinggi bulan ini')
                ->descriptionIcon('heroicon-m-trophy')
                ->color($topEmployeeMin > 0 ? 'danger' : 'gray'),
        ];
    }
}
