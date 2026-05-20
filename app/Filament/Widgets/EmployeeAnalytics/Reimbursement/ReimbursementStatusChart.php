<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Reimbursement;

use App\Models\Finance\ReimbursementRequest;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ReimbursementStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'reimbursementStatusChart';

    protected static ?string $heading = 'Reimbursement Status';

    protected int|string|array $columnSpan = 4;

    protected function getOptions(): array
    {
        $employee = Auth::user()?->employee;

        $startDate = $this->pageFilters['startDate']
            ?? now()->startOfMonth();

        $endDate = $this->pageFilters['endDate']
            ?? now()->endOfMonth();

        $statuses = [
            'pending',
            'approved',
            'rejected',
        ];

        $labels = [
            'Pending',
            'Approved',
            'Rejected',
        ];

        $series = [];

        foreach ($statuses as $status) {

            $series[] = ReimbursementRequest::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', $status)
                ->count();
        }

        return [

            'chart' => [
                'type' => 'donut',
                'height' => 350,
            ],

            'series' => $series,

            'labels' => $labels,

            'legend' => [
                'position' => 'bottom',
            ],

            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                    ],
                ],
            ],

            'dataLabels' => [
                'enabled' => true,
            ],

            'stroke' => [
                'show' => false,
            ],
        ];
    }
}