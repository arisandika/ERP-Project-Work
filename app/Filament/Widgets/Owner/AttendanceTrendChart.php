<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceTrendChart extends ApexChartWidget
{
    use HasPageShield;
    
    protected static ?string $heading = 'Trend Kehadiran (7 Hari)';
    protected static ?string $pollingInterval = '120s';

    protected function getOptions(): array
    {
        $data = Attendance::whereDate('date', '>=', now()->subDays(6))
            ->whereIn('status', ['Hadir', 'Terlambat'])
            ->selectRaw('date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 280,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Kehadiran',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('date')->map(
                    fn ($d) => Carbon::parse($d)->format('d M')
                ),
            ],
            'colors' => ['#22c55e'],
            'stroke' => ['curve' => 'smooth'],
        ];
    }
}
