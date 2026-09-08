<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Employee;
use Filament\Widgets\ChartWidget;

class EmployeeStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Status Karyawan';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 2,
    ];

    protected function getData(): array
    {
        $employees = Employee::forAttendanceReporting();
        $active = (clone $employees)->where('status', 'active')->count();
        $resigned = (clone $employees)->where('status', 'resigned')->count();
        $terminated = (clone $employees)->where('status', 'terminated')->count();

        return [
            'datasets' => [
                [
                    'data' => [$active, $resigned, $terminated],
                    'backgroundColor' => ['#22c55e', '#9ca3af', '#ef4444'],
                ],
            ],
            'labels' => ['Active', 'Resigned', 'Terminated'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
