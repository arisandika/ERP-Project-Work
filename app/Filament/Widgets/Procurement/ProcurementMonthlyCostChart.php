<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseInvoice;
use App\Enums\Procurement\PurchaseInvoiceStatus;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ProcurementMonthlyCostChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Analisis Tren Pengeluaran';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $supplierId = $this->filters['supplier_id'] ?? null;
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        // Optimasi: Hanya satu kueri ke database
        $results = PurchaseInvoice::query()
            ->selectRaw('SUM(grand_total) as total, DATE_FORMAT(invoice_date, "%Y-%m") as month_year')
            ->where('invoice_date', '>=', $sixMonthsAgo)
            // Memanggil Enum dengan benar:
            ->where('status', '!=', PurchaseInvoiceStatus::CANCELLED)
            ->when($supplierId, fn (Builder $q) => $q->where('supplier_id', $supplierId))
            ->groupBy('month_year')
            ->orderBy('month_year', 'asc')
            ->pluck('total', 'month_year')
            ->toArray();

        $data = [];
        $labels = [];

        // Mapping hasil ke 6 bulan terakhir
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M Y');
            // Jika data bulan tersebut tidak ada, set ke 0
            $data[] = (float) ($results[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Nilai Pembelian (IDR)',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
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
            'elements' => [
                'line' => ['tension' => 0.4],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => fn ($value) => 'Rp' . number_format($value / 1000000, 1) . 'M',
                    ],
                ],
            ],
        ];
    }
}
