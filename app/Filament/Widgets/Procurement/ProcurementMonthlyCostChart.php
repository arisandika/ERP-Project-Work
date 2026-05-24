<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseInvoice;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementMonthlyCostChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Monthly Spend Trend';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 8,
    ];

    protected function getData(): array
    {
        $supplierId = $this->filters['supplier_id'] ?? null;

        $startDate = !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])
            : now()->subMonths(5)->startOfMonth();

        $endDate = !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])
            : now()->endOfMonth();

        $driver = DB::connection()->getDriverName();

        $dateSelect = match ($driver) {
            'sqlite' => "strftime('%Y-%m', invoice_date) as month_year",
            'pgsql' => "to_char(invoice_date, 'YYYY-MM') as month_year",
            default => "DATE_FORMAT(invoice_date, '%Y-%m') as month_year",
        };

        $groupBy = match ($driver) {
            'sqlite' => "strftime('%Y-%m', invoice_date)",
            'pgsql' => "to_char(invoice_date, 'YYYY-MM')",
            default => "DATE_FORMAT(invoice_date, '%Y-%m')",
        };

        $results = PurchaseInvoice::query()
            ->selectRaw("SUM(grand_total) as total, {$dateSelect}")
            ->whereBetween('invoice_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->where('status', '!=', 'cancelled')
            ->when(
                $supplierId,
                fn (Builder $q) => $q->where('supplier_id', $supplierId)
            )
            ->groupBy(DB::raw($groupBy))
            ->orderBy('month_year')
            ->pluck('total', 'month_year')
            ->toArray();

        $labels = [];
        $data = [];

        $period = Carbon::parse($startDate)
            ->monthsUntil($endDate);

        foreach ($period as $date) {
            $key = $date->format('Y-m');

            $labels[] = $date->translatedFormat('M Y');

            $data[] = round(
                (($results[$key] ?? 0) / 1000000),
                2
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Spend (Million IDR)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.12)',
                    'fill' => true,
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
                    'callbacks' => [
                        'label' => RawJs::make(<<<JS
                            function(context) {
                                return 'Rp ' + context.raw + ' Juta';
                            }
                        JS),
                    ],
                ],
            ],

            'elements' => [
                'line' => [
                    'tension' => 0.45,
                    'borderWidth' => 3,
                ],

                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 6,
                ],
            ],

            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
