<?php 

namespace App\Filament\Widgets\Owner;

use App\Models\Sales\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InvoiceStatusDonutChart extends ApexChartWidget
{
    // use HasPageShield;
    protected static ?string $heading = 'Status Invoice';

    protected function getOptions(): array
    {
        $data = Invoice::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $data->values()->toArray(),
            'labels' => $data->keys()->toArray(),
            'colors' => ['#22c55e', '#f59e0b', '#ef4444'],
            'legend' => ['position' => 'bottom'],
        ];
    }
}
