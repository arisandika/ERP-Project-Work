<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\StockTransaction;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class StockMovementChart extends ApexChartWidget
{
    protected static ?string $chartId = 'stock_movement_chart';

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $end   = now()->endOfDay();
        $start = now()->subDays(6)->startOfDay();

        $labels      = [];
        $masukData   = [];
        $keluarData  = [];

        $transactions = StockTransaction::query()
            ->selectRaw('DATE(transaction_date) as date')
            ->selectRaw('SUM(CASE WHEN type = "masuk" THEN quantity ELSE 0 END) as total_masuk')
            ->selectRaw('SUM(CASE WHEN type = "keluar" THEN quantity ELSE 0 END) as total_keluar')
            ->whereBetween('transaction_date', [$start, $end])
            ->groupByRaw('DATE(transaction_date)')
            ->get()
            ->keyBy('date');

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();

            $labels[]     = Carbon::parse($day)->isoFormat('DD MMM');
            $masukData[]  = (int) ($transactions[$day]->total_masuk ?? 0);
            $keluarData[] = (int) ($transactions[$day]->total_keluar ?? 0);
        }

        return [
            'chart' => [
                'type'    => 'area',
                'height'  => 320,
                'toolbar' => ['show' => false],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'series' => [
                [
                    'name' => 'Stock Masuk',
                    'data' => $masukData,
                ],
                [
                    'name' => 'Stock Keluar',
                    'data' => $keluarData,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
            ],
            'legend' => [
                'position' => 'top',
            ],
            'title' => [
                'text'  => 'Pergerakan Stock (7 Hari Terakhir)',
                'align' => 'left',
            ],
            'colors' => ['#10B981', '#EF4444'],
        ];
    }
}



