<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueTrendChart extends ApexChartWidget
{
    use HasPageShield;
    protected static ?string $heading = 'Revenue 30 Hari Terakhir';

    protected function getOptions(): array
    {
        $data = \App\Models\Sales\Invoice::where('status', 'paid')
            ->whereDate('invoice_date', '>=', now()->subDays(29))
            ->selectRaw('invoice_date, SUM(grand_total) as total')
            ->groupBy('invoice_date')
            ->orderBy('invoice_date')
            ->get();

        return [
            'chart' => [
                'type' => 'area',
                'height' => 280,
            ],
            'series' => [
                [
                    'name' => 'Revenue',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('invoice_date')->map(
                    fn ($d) => $d->format('d M')
                ),
            ],
            'colors' => ['#3b82f6'],
            'dataLabels' => ['enabled' => false],
        ];
    }
}

