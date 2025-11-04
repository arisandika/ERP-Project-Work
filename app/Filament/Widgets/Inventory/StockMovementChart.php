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
        $end = Carbon::today();
        $start = $end->copy()->subDays(6);

        $labels = [];
        $masukData = [];
        $keluarData = [];

        $raw = StockTransaction::query()
            ->selectRaw('transaction_date, SUM(CASE WHEN type = "masuk" THEN quantity ELSE 0 END) AS masuk, SUM(CASE WHEN type = "keluar" THEN quantity ELSE 0 END) AS keluar')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('transaction_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->transaction_date)->toDateString());

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->toDateString();
            $labels[] = $day->isoFormat('DD MMM');
            $masukData[] = (int) ($raw[$key]->masuk ?? 0);
            $keluarData[] = (int) ($raw[$key]->keluar ?? 0);
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 320,
                'toolbar' => [ 'show' => false ],
            ],
            'stroke' => [ 'curve' => 'smooth' ],
            'series' => [
                [ 'name' => 'Masuk', 'data' => $masukData ],
                [ 'name' => 'Keluar', 'data' => $keluarData ],
            ],
            'xaxis' => [ 'categories' => $labels ],
            'colors' => ['#10B981', '#EF4444'],
            'legend' => [ 'position' => 'top' ],
            'title' => [ 'text' => 'Pergerakan Stok 7 Hari Terakhir', 'align' => 'left' ],
        ];
    }
}


