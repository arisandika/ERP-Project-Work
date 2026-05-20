<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Leave;

use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LeaveBalanceRadialChart extends ApexChartWidget
{
    protected static ?string $chartId = 'leaveBalanceRadialChart';

    protected static ?string $heading = 'Leave Balance';

    protected int|string|array $columnSpan = 4;

    protected function getOptions(): array
    {
        $employee = Auth::user()?->employee;

        $leaveQuota = Leave::query()
            ->sum('days_count');

        $usedLeave = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('total_days');

        $remaining = max($leaveQuota - $usedLeave, 0);

        $percentage = $leaveQuota > 0
            ? round(($remaining / $leaveQuota) * 100)
            : 0;

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 350,
            ],

            'series' => [$percentage],

            'labels' => ['Remaining Leave'],

            'plotOptions' => [
                'radialBar' => [
                    'hollow' => [
                        'size' => '65%',
                    ],

                    'dataLabels' => [
                        'name' => [
                            'fontSize' => '16px',
                        ],

                        'value' => [
                            'fontSize' => '28px',
                        ],
                    ],
                ],
            ],
        ];
    }
}