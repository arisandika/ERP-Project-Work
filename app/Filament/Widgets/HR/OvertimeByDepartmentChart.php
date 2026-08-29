<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Department;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OvertimeByDepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'Total Lembur per Departemen (Bulan Ini)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $departments = Department::with(['employees.attendances' => function ($q) use ($startOfMonth, $endOfMonth) {
            $q
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('clock_out')
                ->whereNotNull('shift_id')
                ->with('shift');
        }])->get();

        $labels = [];
        $data = [];
        $colors = [
            '#6366f1',
            '#8b5cf6',
            '#ec4899',
            '#f97316',
            '#eab308',
            '#22c55e',
            '#14b8a6',
            '#3b82f6',
        ];

        foreach ($departments as $idx => $department) {
            $totalMinutes = 0;
            foreach ($department->employees as $employee) {
                foreach ($employee->attendances as $attendance) {
                    $totalMinutes += $attendance->overtime_minutes;
                }
            }
            if ($totalMinutes > 0) {
                $labels[] = $department->name;
                $data[] = round($totalMinutes / 60, 1);  // konversi ke jam
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Lembur (jam)',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => array_map(fn($c) => $c, array_slice($colors, 0, count($data))),
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Jam Lembur',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
