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
        // 1. Ambil Tanggal dari Filter Halaman
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        // Validasi: Kalau user belum pilih tanggal, pake default bulan ini
        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now();

        return [
            // 1. TOTAL DUIT (Revenue)
            Stat::make('Total Revenue', 'Rp ' . number_format(
                Invoice::where('status', 'paid')
                    ->whereBetween('invoice_date', [$start, $end])
                    ->sum('grand_total'),
                0, ',', '.'
            ))
            ->description('Dari Invoice Lunas')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success')
            ->chart([7, 2, 10, 3, 15, 4, 17]),

            // 2. TOTAL ORDER (Jumlah SO)
            Stat::make('Total Sales Orders',
                SalesOrder::whereBetween('created_at', [$start, $end])->count()
            )
            ->description('SO periode ini')
            ->descriptionIcon('heroicon-m-shopping-cart')
            ->color('primary'),

            // 3. PENDING INVOICE (Total Belum Dibayar)
            Stat::make('Pending Payment', 'Rp ' . number_format(
                Invoice::whereIn('status', ['unpaid', 'partial'])
                    ->whereBetween('invoice_date', [$start, $end])
                    ->sum('grand_total'),
                0, ',', '.'
            ))
            ->description('Total Belum Dibayar')
            ->descriptionIcon('heroicon-m-exclamation-circle')
            ->color('danger'),

            // 4. PROMO & DISKON (Total Cost)
            Stat::make('Total Diskon', 'Rp ' . number_format(
                SalesOrder::whereBetween('created_at', [$start, $end])
                    ->sum('discount_amount'),
                0, ',', '.'
            ))
            ->description('Potongan harga diberikan')
            ->descriptionIcon('heroicon-m-tag')
            ->color('warning'),

            // 5. QUOTATION (Jumlah Penawaran)
            Stat::make('Quotations',
                Quotation::whereBetween('quotation_date', [$start, $end])->count()
            )
            ->description('Penawaran dibuat')
            ->color('gray'),
        ];
    }
}
