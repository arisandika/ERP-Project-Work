<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceStatusDonutChart extends ApexChartWidget
{
    use HasPageShield;

    protected static ?string $heading = 'Komposisi Presensi Hari Ini';

    protected function getOptions(): array
    {
        $today = now()->toDateString();

        $data = Attendance::whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $data->values()->toArray(),
            'labels' => $data->keys()->toArray(),
            'colors' => [
                '#22c55e', // Hadir
                '#f59e0b', // Terlambat
                '#ef4444', // Tidak Presensi Keluar
                '#94a3b8', // Absen
            ],
            'legend' => [
                'position' => 'bottom',
            ],
        ];
    }
}
