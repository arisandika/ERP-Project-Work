<?php

// FIX: Namespace diubah agar masuk ke dalam Resource Finance
namespace App\Filament\Resources\Finance\FinancialRecordResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Finance\FinancialRecord;
use App\Models\Sales\Invoice;
use App\Models\Procurement\PurchaseOrder;

class FinanceOverview extends BaseWidget
{
    // Matikan auto-discover agar tidak muncul di Dashboard utama
    protected static bool $isDiscovered = false;

    protected static ?string $pollingInterval = '60s';

protected function getStats(): array
    {
        $totalPemasukan = FinancialRecord::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = FinancialRecord::where('type', 'pengeluaran')->sum('amount');
        $saldoKas = $totalPemasukan - $totalPengeluaran;

        // FIX ERROR: Tambahkan ->get() sebelum ->sum() agar dihitung oleh Laravel, bukan MySQL
        $totalPiutang = Invoice::whereIn('status', ['sent', 'partial'])->get()->sum('remaining_balance');

        // Lakukan hal yang sama untuk Hutang untuk berjaga-jaga
        $totalHutang = PurchaseOrder::whereIn('status', ['draft', 'sent', 'partial'])->get()->sum('grand_total');

        return [
            // ... (Kodingan Stat::make di bawahnya biarkan sama persis seperti sebelumnya)
            Stat::make('Saldo Kas Tersedia', 'Rp ' . number_format($saldoKas, 0, ',', '.'))
                ->description('Total uang di Buku Besar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldoKas >= 0 ? 'success' : 'danger')
                ->chart([7, 10, 13, 15, 18, 20, 25])
                ->extraAttributes(['class' => 'shadow-sm ring-1 ring-gray-200 dark:ring-gray-800']),

            Stat::make('Piutang Belum Lunas (AR)', 'Rp ' . number_format($totalPiutang, 0, ',', '.'))
                ->description('Tagihan Klien (Invoice)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart([25, 20, 15, 10, 5, 2])
                ->extraAttributes(['class' => 'shadow-sm ring-1 ring-gray-200 dark:ring-gray-800']),

            Stat::make('Hutang Usaha (AP)', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Tagihan Supplier (PO)')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([2, 5, 8, 15, 20, 25])
                ->extraAttributes(['class' => 'shadow-sm ring-1 ring-gray-200 dark:ring-gray-800']),
        ];
    }
}
