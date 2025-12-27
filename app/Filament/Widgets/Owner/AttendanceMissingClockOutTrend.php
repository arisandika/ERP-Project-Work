<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceMissingClockOutTrend extends ApexChartWidget
{
    // use HasPageShield;
    
    protected static ?string $heading = 'Trend Tidak Clock-Out (14 Hari)';

    protected function getOptions(): array
    {
        $data = Attendance::whereDate('date', '>=', now()->subDays(13))
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->selectRaw('date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'chart' => [
                'type' => 'area',
                'height' => 280,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Tidak Clock-Out',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('date')->map(
                    fn ($d) => Carbon::parse($d)->format('d M')
                ),
            ],
            'colors' => ['#ef4444'],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.6,
                    'opacityTo' => 0.1,
                ],
            ],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
