<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Attendance;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\HR\Attendance;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceStatusDonutChart extends ApexChartWidget
{
    use HasEmployeeFilter;

    protected static ?string $chartId = 'attendanceStatusDonut';
    protected static ?string $heading = 'Attendance Status';
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 4,
    ];

    protected function getOptions(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $statuses = ['hadir', 'terlambat', 'absen', 'izin', 'sakit'];
        $series = [];

        foreach ($statuses as $status) {
            $series[] = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$start, $end])
                ->where('status', $status)
                ->count();
        }

        return [
            'chart' => ['type' => 'donut', 'height' => 350],
            'series' => $series,
            'labels' => ['Hadir', 'Terlambat', 'Absen', 'Izin', 'Sakit'],
            'colors' => ['#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6'],
            'legend' => ['position' => 'bottom'],
            'plotOptions' => ['pie' => ['donut' => ['size' => '65%']]],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['show' => false],
        ];
    }
}
