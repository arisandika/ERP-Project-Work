<?php

namespace App\Filament\Widgets\Owner;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\Invoice;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SalesOrderVsInvoiceChart extends ApexChartWidget
{
    protected static ?string $heading = 'Order vs Invoice (6 Bulan)';

    protected function getOptions(): array
    {
        $months = collect(range(0, 5))->map(
            fn ($i) => now()->subMonths($i)->format('Y-m')
        )->reverse();

        $orders = [];
        $invoices = [];

        foreach ($months as $month) {
            $orders[] = SalesOrder::whereRaw('DATE_FORMAT(order_date, "%Y-%m") = ?', [$month])->count();
            $invoices[] = Invoice::whereRaw('DATE_FORMAT(invoice_date, "%Y-%m") = ?', [$month])->count();
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
            ],
            'series' => [
                ['name' => 'Order', 'data' => $orders],
                ['name' => 'Invoice', 'data' => $invoices],
            ],
            'xaxis' => [
                'categories' => $months->map(
                    fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M')
                ),
            ],
            'colors' => ['#3b82f6', '#f97316'],
            'plotOptions' => [
                'bar' => ['columnWidth' => '45%'],
            ],
        ];
    }
}
