<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Attendance;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\HR\Attendance;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceHeatmapChart extends ApexChartWidget
{
    use HasEmployeeFilter;

    protected static ?string $chartId = 'attendanceHeatmap';
    protected static ?string $heading = 'Attendance Heatmap';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];

    protected function getOptions(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('DAYOFWEEK(date) as day, HOUR(clock_in) as hour, COUNT(*) as count')
            ->whereNotNull('clock_in')
            ->groupBy('day', 'hour')
            ->get();

        $series = [];
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($day = 1; $day <= 7; $day++) {
            $data = [];
            for ($hour = 6; $hour <= 20; $hour++) {
                $found = $attendances->first(fn($a) => $a->day == $day && $a->hour == $hour);
                $data[] = ['x' => "{$hour}:00", 'y' => $found ? $found->count : 0];
            }
            $series[] = ['name' => $days[$day - 1] ?? "Day {$day}", 'data' => $data];
        }

        return [
            'chart' => ['type' => 'heatmap', 'height' => 350, 'toolbar' => ['show' => false]],
            'series' => $series,
            'plotOptions' => [
                'heatmap' => [
                    'shadeIntensity' => 0.5,
                    'colorScale' => [
                        'ranges' => [
                            ['from' => 0, 'to' => 0, 'color' => '#f3f4f6', 'name' => 'Tidak Ada'],
                            ['from' => 1, 'to' => 3, 'color' => '#bbf7d0', 'name' => 'Sedikit'],
                            ['from' => 4, 'to' => 6, 'color' => '#10b981', 'name' => 'Normal'],
                            ['from' => 7, 'to' => 99, 'color' => '#065f46', 'name' => 'Sibuk'],
                        ],
                    ],
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => false],
        ];
    }
}
