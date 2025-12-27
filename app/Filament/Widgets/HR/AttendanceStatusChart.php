<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceStatusChart extends ApexChartWidget
{
    // use HasPageShield;
    protected static ?string $chartId = 'attendanceStatusChart';
    protected static ?string $heading = 'Status Presensi Karyawan Hari Ini';
    protected static ?string $subheading = 'Data kehadiran harian berdasarkan status absensi';
    protected static ?string $pollingInterval = null; // disable auto refresh
    protected static bool $darkMode = true;
    protected static bool $isCollapsible = true;
    protected static ?int $contentHeight = 350;

    protected function getOptions(): array
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();

        $attendances = Attendance::with('employee')
            ->whereDate('date', $today)
            ->get();

        $present = $attendances->where('status', 'Hadir Tepat Waktu')->count();
        $late = $attendances->where('status', 'Terlambat')->count();

        $leaveToday = LeaveRequest::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'approved')
            ->count();

        $absent = $totalEmployees - ($present + $late + $leaveToday);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'toolbar' => [
                    'show' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Jumlah Karyawan',
                    'data' => [$present, $late, $absent, $leaveToday],
                ],
            ],
            'xaxis' => [
                'categories' => ['Hadir Tepat Waktu', 'Terlambat', 'Belum Presensi', 'Cuti / Izin'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                        'colors' => ['#10b981', '#f59e0b', '#ef4444', '#6366f1'],
                    ],
                ],
            ],
            'colors' => ['#3b82f6'],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'colors' => ['#fff'],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'horizontal' => false,
                ],
            ],
            'theme' => [
                'mode' => 'dark',
            ],
            'title' => [
                'text' => 'Rekapitulasi Presensi Karyawan — ' . $today->translatedFormat('d F Y'),
                'align' => 'center',
                'style' => [
                    'fontFamily' => 'inherit',
                    'fontWeight' => 600,
                    'color' => '#374151',
                ],
            ],
        ];
    }
}
