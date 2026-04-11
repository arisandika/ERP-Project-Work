<?php

namespace App\Filament\Resources\Finance\FinancialRecordResource\Widgets;

use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Sales\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalPemasukan = (float) FinancialRecord::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = (float) FinancialRecord::where('type', 'pengeluaran')->sum('amount');
        $saldoKas = $totalPemasukan - $totalPengeluaran;

        $totalPiutang = Invoice::whereIn('status', ['sent', 'partial'])
            ->get()
            ->sum('remaining_balance');

        $totalHutang = PurchaseOrder::whereIn('status', ['sent', 'partial'])
            ->get()
            ->sum('grand_total');

        return [
            Stat::make('Saldo Kas Tersedia', 'Rp ' . number_format($saldoKas, 0, ',', '.'))
                ->description('Total uang di Buku Besar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 10, 13, 15, 18, 20, 25])
                ->extraAttributes([
                    'class' => 'bg-success-50 dark:bg-success-900/20 border-l-4 border-success-500 shadow-sm',
                ]),

            Stat::make('Piutang Belum Lunas (AR)', 'Rp ' . number_format($totalPiutang, 0, ',', '.'))
                ->description('Tagihan Klien (Invoice)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart([25, 20, 15, 10, 5, 2])
                ->extraAttributes([
                    'class' => 'bg-warning-50 dark:bg-warning-900/20 border-l-4 border-warning-500 shadow-sm',
                ]),

            Stat::make('Hutang Usaha (AP)', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Tagihan Supplier (PO)')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([2, 5, 8, 15, 20, 25])
                ->extraAttributes([
                    'class' => 'bg-danger-50 dark:bg-danger-900/20 border-l-4 border-danger-500 shadow-sm',
                ]),
        ];
    }
}
