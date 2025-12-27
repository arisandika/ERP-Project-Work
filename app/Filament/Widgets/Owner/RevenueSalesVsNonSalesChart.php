<?php 

namespace App\Filament\Widgets\Owner;

use App\Models\Sales\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueSalesVsNonSalesChart extends ApexChartWidget
{
    use HasPageShield;
    protected static ?string $heading = 'Kontribusi Revenue';

    protected function getOptions(): array
    {
        $sales = Invoice::whereNotNull('nx_employee_id')
            ->where('status', 'paid')
            ->sum('grand_total');

        $nonSales = Invoice::whereNull('nx_employee_id')
            ->where('status', 'paid')
            ->sum('grand_total');

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 320,
            ],
            'series' => [$sales, $nonSales],
            'labels' => ['Sales', 'Non-Sales'],
            'colors' => ['#3b82f6', '#a855f7'],
        ];
    }
}
