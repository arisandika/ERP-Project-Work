<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Reimbursement;

use App\Models\Finance\ReimbursementRequest;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyReimbursementChart extends ApexChartWidget
{
    protected static ?string $chartId = 'monthlyReimbursementChart';

    protected static ?string $heading = 'Monthly Reimbursement Trend';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 8,
    ];

    protected function getOptions(): array
    {
        $employee = Auth::user()?->employee;

        $startDate = $this->pageFilters['startDate']
            ?? now()->startOfMonth();

        $endDate = $this->pageFilters['endDate']
            ?? now()->endOfMonth();

        $period = CarbonPeriod::create($startDate, $endDate);

        $categories = [];

        $amounts = [];

        foreach ($period as $date) {

            $categories[] = $date->format('d M');

            $amount = ReimbursementRequest::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->where('status', 'approved')
                ->sum('amount');

            $amounts[] = round($amount);
        }

        return [

            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'toolbar' => [
                    'show' => false,
                ],
            ],

            'series' => [
                [
                    'name' => 'Reimbursement',
                    'data' => $amounts,
                ],
            ],

            'xaxis' => [
                'categories' => $categories,
            ],

            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'columnWidth' => '45%',
                ],
            ],

            'dataLabels' => [
                'enabled' => false,
            ],

            'yaxis' => [
                'labels' => [
                    'formatter' => 'function(val) {
                        return "Rp " + val.toLocaleString();
                    }',
                ],
            ],

            'tooltip' => [
                'y' => [
                    'formatter' => 'function(val) {
                        return "Rp " + val.toLocaleString();
                    }',
                ],
            ],
        ];
    }
}