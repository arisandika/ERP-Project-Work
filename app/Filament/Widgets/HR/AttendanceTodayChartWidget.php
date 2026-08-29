<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Filament\Widgets\ChartWidget;

class AttendanceTodayChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Presensi Hari Ini';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $today          = Attendance::whereDate('date', now())->get();
        $totalEmployees = Employee::where('status', 'active')->count();

        $hadir         = $today->where('status', 'hadir')->count();
        $terlambat     = $today->where('status', 'terlambat')->count();
        $izin          = $today->where('status', 'izin')->count();
        $cuti          = $today->where('status', 'cuti')->count();
        $belumPresensi = $totalEmployees - $today->count();

        return [
            'datasets' => [
                [
                    'label'           => 'Karyawan',
                    'data'            => [$hadir, $terlambat, $izin, $cuti, max($belumPresensi, 0)],
                    'backgroundColor' => ['#22c55e', '#f59e0b', '#eab308', '#3b82f6', '#ef4444'],
                ],
            ],
            'labels'   => ['Hadir', 'Terlambat', 'Izin', 'Cuti', 'Belum Presensi'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
