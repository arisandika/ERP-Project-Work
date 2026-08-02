<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Attendance;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\HR\Attendance;
use Carbon\CarbonPeriod;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceTrendChart extends ApexChartWidget
{
    use HasEmployeeFilter;

    protected static ?string $chartId = 'attendanceTrendChart';
    protected static ?string $heading = 'Attendance Trend';
    protected int|string|array $columnSpan = 8;

    protected function getOptions(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $period = CarbonPeriod::create($start, '1 week', $end);
        $categories = [];
        $hadir = [];
        $terlambat = [];

        foreach ($period as $date) {
            $weekEnd = $date->copy()->endOfWeek();
            $categories[] = $date->format('d M');

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$date, $weekEnd])
                ->get(['status']);

            $hadir[] = $attendances->whereIn('status', ['hadir'])->count();
            $terlambat[] = $attendances->where('status', 'terlambat')->count();
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 350, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Hadir', 'data' => $hadir],
                ['name' => 'Terlambat', 'data' => $terlambat],
            ],
            'xaxis' => ['categories' => $categories],
            'colors' => ['#10b981', '#f59e0b'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '60%']],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['show' => false],
            'legend' => ['position' => 'top'],
        ];
    }
}
