<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tren Pendapatan';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 122,
        'xl' => 12,
    ];

    protected function getData(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now();

        $revenues = Invoice::select(
            DB::raw('DATE(invoice_date) as date'),
            DB::raw('SUM(grand_total) as total')
        )
            ->where('status', 'paid')
            ->whereBetween('invoice_date', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $labels = [];

        foreach ($revenues as $revenue) {
            $labels[] = Carbon::parse($revenue->date)->format('d M');
            $data[] = (float) $revenue->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                    'pointBackgroundColor' => '#10b981',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(ctx) { return "Rp " + ctx.raw.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + (value/1000000).toFixed(0) + "M"; }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
