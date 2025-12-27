<?php

namespace App\Filament\Widgets\Owner;

use App\Models\Sales\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SalesRevenueMonthlyChart extends ApexChartWidget
{
    // use HasPageShield;
    protected static ?string $heading = 'Revenue Bulanan (12 Bulan)';

    protected function getOptions(): array
    {
        $data = Invoice::where('status', 'paid')
            ->whereDate('invoice_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(grand_total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 320,
            ],
            'series' => [
                [
                    'name' => 'Revenue',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('month')->map(
                    fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y')
                ),
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'colors' => ['#22c55e'],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
