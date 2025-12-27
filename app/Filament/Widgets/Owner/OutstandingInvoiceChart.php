<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OutstandingInvoiceChart extends ApexChartWidget
{
    // use HasPageShield;
    protected static ?string $heading = 'Invoice Belum Dibayar';

    protected function getOptions(): array
    {
        $data = \App\Models\Sales\Invoice::where('status', '!=', 'paid')
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 260,
            ],
            'series' => [
                [
                    'name' => 'Outstanding',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('month'),
            ],
            'colors' => ['#f59e0b'],
        ];
    }
}

