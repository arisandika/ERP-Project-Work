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
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getStats(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate   = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        $cacheKey = "sales_stats_{$start->format('Ymd')}_{$end->format('Ymd')}";

        $stats = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($start, $end) {
            $revenue = Invoice::where('status', 'paid')
                ->whereBetween('invoice_date', [$start, $end])
                ->sum('grand_total');

            $pending = Invoice::whereIn('status', ['unpaid', 'partial'])
                ->whereBetween('invoice_date', [$start, $end])
                ->sum('remaining_balance');

            $orders = SalesOrder::whereBetween('order_date', [$start, $end])
                ->count();

            $invoices = Invoice::whereBetween('invoice_date', [$start, $end])->count();

            return compact('revenue', 'pending', 'orders', 'invoices');
        });

        return [
            Stat::make('Total Revenue', 'Rp ' . number_format($stats['revenue'], 0, ',', '.'))
                ->description('Pendapatan dari invoice lunas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([12, 18, 15, 22, 19, 25, round($stats['revenue'] / max($stats['invoices'], 1))]),

            Stat::make('Pending Payment', 'Rp ' . number_format($stats['pending'], 0, ',', '.'))
                ->description('Tagihan belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->chart([8, 5, 12, 7, 9, 4, round($stats['pending'] / max($stats['invoices'], 1))]),

            Stat::make('Total Sales Orders', number_format($stats['orders'], 0, ',', '.'))
                ->description('Jumlah pesanan periode ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->chart([3, 5, 4, 7, 6, 8, $stats['orders']]),

            Stat::make('Total Invoices', number_format($stats['invoices'], 0, ',', '.'))
                ->description('Invoice yang dibuat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
