<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use App\Models\Sales\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SalesSummaryStats extends BaseWidget
{
    // Mengaktifkan sifat reaktif agar widget merespons filter tanggal dari Dashboard
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        // 1. Ambil rentang tanggal dari filter, gunakan bulan ini sebagai default
        $startDate = $this->filters['start_date'] ?? null;
        $endDate   = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        // 2. Buat Cache Key yang unik berdasarkan filter tanggal
        $cacheKey = "sales_stats_{$start->format('Ymd')}_{$end->format('Ymd')}";

        // 3. Ambil dari Cache, atau Query ke Database jika Cache kosong/kadaluarsa (TTL: 15 Menit)
        $stats = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($start, $end) {
            return [
                'revenue' => Invoice::where('status', 'paid')
                                    ->whereBetween('invoice_date', [$start, $end])
                                    ->sum('grand_total'),

                'pending' => Invoice::whereIn('status', ['unpaid', 'partial'])
                                    ->whereBetween('invoice_date', [$start, $end])
                                    ->sum('remaining_balance'),

                'orders'  => SalesOrder::whereBetween('order_date', [$start, $end])
                                    ->count(),
            ];
        });

        // 4. Format dan Render UI Widget
        return [
            Stat::make('Total Revenue', 'IDR ' . number_format($stats['revenue'], 0, ',', '.'))
                ->description('Pendapatan dari Invoice Lunas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Pending Payment', 'IDR ' . number_format($stats['pending'], 0, ',', '.'))
                ->description('Tagihan belum dibayar (Piutang)')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Total Sales Orders', number_format($stats['orders'], 0, ',', '.'))
                ->description('Jumlah pesanan periode ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
        ];
    }
}
