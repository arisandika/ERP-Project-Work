<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class SalesSummaryStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now();

        return [
            Stat::make('Total Revenue', 'IDR ' . number_format(
                Invoice::where('status', 'paid')
                    ->whereBetween('invoice_date', [$start, $end])
                    ->sum('grand_total'),
                0,
                ',',
                '.'
            ))
                ->description('Dari Invoice Lunas')
                ->color('success'),

            Stat::make(
                'Total Sales Orders',
                SalesOrder::whereBetween('created_at', [$start, $end])->count()
            )
                ->description('SO periode ini')
                ->color('primary'),

            Stat::make('Pending Payment', 'IDR ' . number_format(
                Invoice::whereIn('status', ['unpaid', 'partial'])
                    ->whereBetween('invoice_date', [$start, $end])
                    ->sum('grand_total'),
                0,
                ',',
                '.'
            ))
                ->description('Total Belum Dibayar')
                ->color('danger'),

            Stat::make('Total Diskon', 'IDR ' . number_format(
                SalesOrder::whereBetween('created_at', [$start, $end])
                    ->sum('discount_amount'),
                0,
                ',',
                '.'
            ))
                ->description('Potongan harga diberikan')
                ->color('warning'),

            Stat::make(
                'Quotations',
                Quotation::whereBetween('quotation_date', [$start, $end])->count()
            )
                ->description('Penawaran dibuat')
                ->color('gray'),
        ];
    }
}
