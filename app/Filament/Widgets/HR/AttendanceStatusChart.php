<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'attendanceStatusChart';

    protected static ?string $heading = 'Status Presensi Hari Ini';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 1,
    ];

    protected static ?string $pollingInterval = null;

    protected static bool $darkMode = true;

    protected static bool $isCollapsible = true;

    protected static ?int $contentHeight = 200;

    protected function getOptions(): array
    {
        $today = Carbon::today();

        $totalEmployees = Employee::forAttendanceReporting()->count();

        $attendances = Attendance::forAttendanceReporting()
            ->whereDate('date', $today)
            ->get();

        $present = $attendances->where('status', 'hadir')->count();
        $late = $attendances->where('status', 'terlambat')->count();

        $leaveToday = LeaveRequest::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'approved')
            ->whereDoesntHave(
                'employee.user.roles',
                fn ($query) => $query->where('name', 'super_admin')
            )
            ->count();

        $absent = max($totalEmployees - ($present + $late + $leaveToday), 0);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 200,
                'toolbar' => [
                    'show' => false,
                ],
                'fontFamily' => 'inherit',
            ],
            'series' => [
                [
                    'name' => 'Jumlah',
                    'data' => [
                        $present,
                        $late,
                        $absent,
                        $leaveToday
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => [
                    'Hadir',
                    'Terlambat',
                    'Belum',
                    'Cuti/Izin'
                ],
                'labels' => [
                    'style' => [
                        'fontSize' => '13px',
                        'fontWeight' => 500,
                    ],
                ],
            ],
            'yaxis' => [
                'min' => 0,
                'forceNiceScale' => true,
                'decimalsInFloat' => 0,
                'labels' => [
                    'style' => [
                        'fontSize' => '12px',
                    ],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 1,
                    'columnWidth' => '35%',
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'offsetY' => 0,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 600,
                ],
            ],
            'grid' => [
                'borderColor' => '#374151',
                'strokeDashArray' => 4,
            ],
            'colors' => [
                '#f59e0b',  // terlambat
                '#ef4444',  // absen
                '#10b981',  // hadir
                '#6366f1',  // cuti
            ],
            'legend' => [
                'show' => false,
            ],
            'title' => [
                'align' => 'left',
                'style' => [
                    'fontSize' => '14px',
                    'fontWeight' => 600,
                ],
            ],
        ];
    }
}
