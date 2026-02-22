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

    protected static ?string $heading = null;

    protected function getHeading(): string
    {
        return 'Status Presensi ' . Carbon::today()->locale('id')->translatedFormat('l, d F Y');
    }

    protected int|string|array $columnSpan = [
        'default' => 2,
        'md' => 1,
    ];

    protected static ?string $pollingInterval = null;

    protected static bool $darkMode = true;

    protected static bool $isCollapsible = true;

    protected static ?int $contentHeight = 200;

    protected function getOptions(): array
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();

        $attendances = Attendance::whereDate('date', $today)->get();

        $present = $attendances->where('status', 'Hadir Tepat Waktu')->count();
        $late = $attendances->where('status', 'Terlambat')->count();

        $leaveToday = LeaveRequest::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'approved')
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
                'labels' => [
                    'style' => [
                        'fontSize' => '12px',
                    ],
                ],
            ],

            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 1,
                    'columnWidth' => '45%',
                ],
            ],

            'dataLabels' => [
                'enabled' => true,
                'offsetY' => -6,
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
                '#10b981', // hadir
                '#f59e0b', // terlambat
                '#ef4444', // absen
                '#6366f1', // cuti
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
