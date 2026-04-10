<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseInvoice;
use App\Models\Procurement\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProcurementStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Total PO yang dibuat bulan ini
        $poThisMonth = PurchaseOrder::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // 2. Total Hutang Dagang (dari tagihan yang belum lunas)
        $totalHutang = PurchaseInvoice::whereIn('status', ['unpaid', 'partial'])
            ->sum('remaining_balance');

        // 3. Total Pemasok Aktif
        $totalSupplier = Supplier::count();

        return [
            Stat::make('Purchase Order (Bulan Ini)', $poThisMonth)
                ->description('Total dokumen PO dibuat')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Total Hutang Dagang (AP)', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Menunggu Pembayaran ke Supplier')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->extraAttributes(['class' => 'font-bold']),

            Stat::make('Total Supplier', $totalSupplier)
                ->description('Vendor terdaftar')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),
        ];
    }
}
