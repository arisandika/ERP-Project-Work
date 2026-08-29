<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Leave;

use App\Models\HR\LeaveRequest;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LeaveTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'leaveTrendChart';

    protected static ?string $heading = 'Leave Usage Trend';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 8,
    ];

    protected function getOptions(): array
    {
        $employee = Auth::user()?->employee;

        $startDate = $this->pageFilters['startDate'] ?? now()->startOfMonth();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfMonth();

        $period = CarbonPeriod::create($startDate, '1 week', $endDate);

        $categories = [];
        $data = [];

        foreach ($period as $date) {

            $weekEnd = $date->copy()->endOfWeek();

            $categories[] = $date->format('d M');

            $count = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('start_date', [$date, $weekEnd])
                ->sum('total_days');

            $data[] = $count;
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
                'toolbar' => [
                    'show' => false,
                ],
            ],

            'series' => [
                [
                    'name' => 'Leave Days',
                    'data' => $data,
                ],
            ],

            'xaxis' => [
                'categories' => $categories,
            ],

            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],

            'markers' => [
                'size' => 5,
            ],

            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }
}