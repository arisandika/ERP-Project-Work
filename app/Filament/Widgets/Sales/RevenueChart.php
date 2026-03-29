<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tren Pendapatan (Revenue)';
    protected static ?int $sort = 2; // Tampil setelah StatsOverview

    protected function getData(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now();

        // LOGIKA ARSITEKTUR: Eager calculation on database level
        // Gunakan fungsi agregat DB, jangan loop data Eloquent Collection di memory (Bisa OOM)
        $revenues = Invoice::select(
            DB::raw('DATE(invoice_date) as date'),
            DB::raw('SUM(grand_total) as total')
        )
            ->where('status', 'paid') // Hanya hitung invoice lunas
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
                    'label' => 'Total Pendapatan (IDR)',
                    'data' => $data,
                    'borderColor' => '#10b981', // Tailwind Emerald 500
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
