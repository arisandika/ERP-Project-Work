<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\StockTransaction;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MovementAnalysisChart extends ChartWidget
{
    protected static ?string $heading = 'Analisis Arus Barang (30 Hari)';
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn($d) => now()->subDays($d)->format('Y-m-d'));

        $chartData = Cache::remember('movement_analysis_30d', now()->addHours(1), function () use ($days) {
            $startDate = now()->subDays(30)->startOfDay();

            $transactions = StockTransaction::where('transaction_date', '>=', $startDate)
                ->selectRaw('DATE(transaction_date) as date, type, SUM(quantity) as total')
                ->groupBy('date', 'type')
                ->get();

            $inbound = $transactions->where('type', 'masuk')->pluck('total', 'date');
            $outbound = $transactions->where('type', 'keluar')->pluck('total', 'date');

            return [
                'inbound' => $days->map(fn($date) => $inbound[$date] ?? 0)->toArray(),
                'outbound' => $days->map(fn($date) => $outbound[$date] ?? 0)->toArray(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Barang Masuk',
                    'data' => $chartData['inbound'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'Barang Keluar',
                    'data' => $chartData['outbound'],
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $days->map(fn($date) => Carbon::parse($date)->format('d M'))->toArray(),
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
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
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
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
