<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Department;
use Filament\Widgets\ChartWidget;

class EmployeeByDepartmentChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Karyawan per Departemen';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 2,
    ];

    protected function getData(): array
    {
        $departments = Department::withCount(['employees' => function ($query) {
            $query->where('status', 'active');
        }])->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Karyawan',
                    'data'            => $departments->pluck('employees_count')->toArray(),
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels'   => $departments->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
