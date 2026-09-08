<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;

class AttendanceOvertimeSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];

    protected function getStats(): array
    {
        $employee = auth()->user()?->employee;

        if (!$employee) {
            return [];
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $attendances = Attendance::forAttendanceReporting()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereNotNull('clock_out')
            ->whereNotNull('shift_id')
            ->with('shift')
            ->get();

        $totalMinutes = 0;
        $overtimeSessions = 0;
        $maxMinutes = 0;
        $maxDate = null;

        foreach ($attendances as $attendance) {
            $ot = $attendance->overtime_minutes;
            if ($ot > 0) {
                $totalMinutes += $ot;
                $overtimeSessions++;
                if ($ot > $maxMinutes) {
                    $maxMinutes = $ot;
                    $maxDate = $attendance->date;
                }
            }
        }

        $totalHours = intdiv($totalMinutes, 60);
        $totalMins = $totalMinutes % 60;
        $totalLabel = $totalMinutes > 0
            ? ($totalHours > 0 ? "{$totalHours} jam {$totalMins} menit" : "{$totalMins} menit")
            : '0 jam 0 menit';

        $avgMinutes = $overtimeSessions > 0 ? intdiv($totalMinutes, $overtimeSessions) : 0;
        $avgHours = intdiv($avgMinutes, 60);
        $avgMins = $avgMinutes % 60;
        $avgLabel = $avgMinutes > 0
            ? ($avgHours > 0 ? "{$avgHours} jam {$avgMins} menit" : "{$avgMins} menit")
            : '—';

        $maxLabel = $maxMinutes > 0
            ? (intdiv($maxMinutes, 60) > 0
                ? intdiv($maxMinutes, 60) . 'jam ' . ($maxMinutes % 60) . 'menit'
                : ($maxMinutes % 60) . ' menit')
            : '—';
        $maxDateLabel = $maxDate
            ? Carbon::parse($maxDate)->translatedFormat('d M Y')
            : '—';

        return [
            Stat::make('Total Lembur Bulan Ini', $totalLabel)
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->color($totalMinutes > 0 ? 'warning' : 'gray'),
            Stat::make('Rata-rata Lembur / Sesi', $avgLabel)
                ->description("{$overtimeSessions} sesi lembur bulan ini")
                ->color($avgMinutes > 0 ? 'info' : 'gray'),
            Stat::make('Sesi Lembur Terpanjang', $maxLabel)
                ->description($maxDateLabel)
                ->color($maxMinutes > 0 ? 'danger' : 'gray'),
            Stat::make('Total Sesi Lembur', $overtimeSessions)
                ->description('Hari dengan lembur bulan ini')
                ->color($overtimeSessions > 0 ? 'warning' : 'gray'),
        ];
    }
}
