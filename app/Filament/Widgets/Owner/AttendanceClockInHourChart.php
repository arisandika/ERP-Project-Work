<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceClockInHourChart extends ApexChartWidget
{
    
    
    protected static ?string $heading = 'Distribusi Jam Clock-In (30 Hari)';

    protected function getOptions(): array
    {
        $data = Attendance::whereNotNull('clock_in')
            ->whereDate('date', '>=', now()->subDays(29))
            ->selectRaw('HOUR(clock_in) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Jumlah Presensi',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('hour')->map(
                    fn ($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'
                ),
                'title' => ['text' => 'Jam Masuk'],
            ],
            'colors' => ['#3b82f6'],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
