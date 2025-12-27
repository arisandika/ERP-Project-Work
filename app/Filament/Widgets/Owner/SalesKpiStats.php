<?php

namespace App\Filament\Widgets\Owner;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\Sales\Invoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesPerson;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesKpiStats extends StatsOverviewWidget
{
    use HasPageShield;
    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = '2';
    
    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = now();
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        return [
            // SALES KPI
            Stat::make(
                'Revenue Bulan Ini',
                'Rp ' . number_format(
                    Invoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                        ->where('status', 'paid')
                        ->sum('grand_total'),
                    0,
                    ',',
                    '.'
                )
            )
                ->description('Total pembayaran masuk')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->chart(
                    Invoice::where('status', 'paid')
                        ->whereDate('invoice_date', '>=', now()->subDays(30))
                        ->selectRaw('SUM(grand_total) as total')
                        ->groupByRaw('DATE(invoice_date)')
                        ->pluck('total')
                        ->toArray()
                )
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make(
                'Order Bulan Ini',
                SalesOrder::whereBetween('order_date', [$monthStart, $monthEnd])->count()
            )
                ->description('Total order masuk')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->chart(
                    SalesOrder::whereDate('order_date', '>=', now()->subDays(30))
                        ->selectRaw('COUNT(*) as total')
                        ->groupByRaw('DATE(order_date)')
                        ->pluck('total')
                        ->toArray()
                )
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make(
                'Invoice Belum Dibayar',
                Invoice::where('status', '!=', 'paid')->count()
            )
                ->description('Perlu follow-up')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->icon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make(
                'Sales Aktif',
                SalesPerson::where('status', 'active')->count()
            )
                ->description('Sales aktif saat ini')
                ->descriptionIcon('heroicon-o-briefcase')
                ->icon('heroicon-o-briefcase')
                ->color('info'),
        ];
    }
}