<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Attendance;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\HR\Attendance;
use Carbon\CarbonPeriod;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class WorkHoursChart extends ApexChartWidget
{
    use HasEmployeeFilter;

    protected static ?string $chartId = 'workHoursChart';
    protected static ?string $heading = 'Work Hours Trend';
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 6,
    ];

    protected function getOptions(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $period = CarbonPeriod::create($start, '1 week', $end);
        $categories = [];
        $hours = [];

        foreach ($period as $date) {
            $weekEnd = $date->copy()->endOfWeek();
            $categories[] = $date->format('d M');

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$date, $weekEnd])
                ->whereNotNull('clock_out')
                ->get(['clock_in', 'clock_out']);

            $totalMinutes = $attendances->sum(fn($a) => $a->clock_in->diffInMinutes($a->clock_out));
            $hours[] = round($totalMinutes / 60, 1);
        }

        return [
            'chart' => ['type' => 'area', 'height' => 350, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Jam Kerja', 'data' => $hours],
            ],
            'xaxis' => ['categories' => $categories],
            'colors' => ['#3b82f6'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'fill' => ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.3, 'opacityTo' => 0.1]],
            'dataLabels' => ['enabled' => false],
            'yaxis' => [
                'title' => ['text' => 'Jam'],
                'labels' => ['formatter' => 'function(v) { return v + "h"; }'],
            ],
        ];
    }
}
