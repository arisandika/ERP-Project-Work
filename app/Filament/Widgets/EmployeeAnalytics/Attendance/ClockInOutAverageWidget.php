<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Attendance;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\HR\Attendance;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ClockInOutAverageWidget extends BaseWidget
{
    use HasEmployeeFilter;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected function getStats(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->whereNotNull('clock_in')
            ->get(['clock_in', 'clock_out']);

        $avgClockIn = '-';
        $avgClockOut = '-';
        $avgWorkHours = 0;

        if ($attendances->isNotEmpty()) {
            // Average clock_in (as minutes from midnight)
            $avgInMinutes = $attendances->avg(fn($a) => $a->clock_in->hour * 60 + $a->clock_in->minute);
            $avgInHours = floor($avgInMinutes / 60);
            $avgInMins = round($avgInMinutes % 60);
            $avgClockIn = sprintf('%02d:%02d', $avgInHours, $avgInMins);

            // Average clock_out
            $withClockOut = $attendances->filter(fn($a) => $a->clock_out);
            if ($withClockOut->isNotEmpty()) {
                $avgOutMinutes = $withClockOut->avg(fn($a) => $a->clock_out->hour * 60 + $a->clock_out->minute);
                $avgOutHours = floor($avgOutMinutes / 60);
                $avgOutMins = round($avgOutMinutes % 60);
                $avgClockOut = sprintf('%02d:%02d', $avgOutHours, $avgOutMins);

                $totalMinutes = $withClockOut->sum(fn($a) => $a->clock_in->diffInMinutes($a->clock_out));
                $avgWorkHours = round($totalMinutes / $withClockOut->count() / 60, 1);
            }
        }

        return [
            Stat::make('Rata-rata Clock In', $avgClockIn)
                ->description('Waktu masuk rata-rata')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('info'),
            Stat::make('Rata-rata Clock Out', $avgClockOut)
                ->description('Waktu pulang rata-rata')
                ->descriptionIcon('heroicon-m-arrow-left-on-rectangle')
                ->color('primary'),
            Stat::make('Rata-rata Jam Kerja', $avgWorkHours . ' jam')
                ->description('Per hari dalam periode')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgWorkHours >= 8 ? 'success' : 'warning'),
        ];
    }
}
