<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use Filament\Widgets\ChartWidget;

class AttendanceTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Kehadiran 30 Hari Terakhir';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 10,
    ];

    protected function getData(): array
    {
        $labels = [];
        $hadirData = [];
        $terlambatData = [];
        $absenData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d M');

            $dayData = Attendance::whereDate('date', $date)->get();
            $hadirData[] = $dayData->where('status', 'hadir')->count();
            $terlambatData[] = $dayData->where('status', 'terlambat')->count();
            $absenData[] = $dayData->where('status', 'absen')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadirData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $terlambatData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Absen',
                    'data' => $absenData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
