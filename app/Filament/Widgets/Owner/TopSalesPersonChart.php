<?php

namespace App\Filament\Widgets\Owner;

use App\Models\Sales\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopSalesPersonChart extends ApexChartWidget
{
    // use HasPageShield;
    protected static ?string $heading = 'Top 5 Sales (Revenue Bulan Ini)';

    protected function getOptions(): array
    {
        $data = Invoice::where('status', 'paid')
            ->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('nx_employee_id, SUM(grand_total) as total')
            ->groupBy('nx_employee_id')
            ->orderByDesc('total')
            ->take(5)
            ->with('employee')
            ->get();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
            ],
            'series' => [
                [
                    'name' => 'Revenue',
                    'data' => $data->pluck('total'),
                ],
            ],
            'xaxis' => [
                'categories' => $data->pluck('employee.full_name'),
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                ],
            ],
            'colors' => ['#6366f1'],
        ];
    }
}
