<?php

namespace App\Filament\Resources\Finance\FinancialRecordResource\Widgets;

use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Sales\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinanceOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function formatRupiah(float|int|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalPemasukanKeseluruhan = (float) FinancialRecord::query()
            ->where('type', 'pemasukan')
            ->sum('amount');

        $totalPengeluaranKeseluruhan = (float) FinancialRecord::query()
            ->where('type', 'pengeluaran')
            ->sum('amount');

        $saldoKas = $totalPemasukanKeseluruhan - $totalPengeluaranKeseluruhan;

        $pemasukanBulanIni = (float) FinancialRecord::query()
            ->where('type', 'pemasukan')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $pengeluaranBulanIni = (float) FinancialRecord::query()
            ->where('type', 'pengeluaran')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $labaBersihBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

        $jumlahTransaksiBulanIni = FinancialRecord::query()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $totalPiutang = (float) Invoice::query()
            ->whereIn('status', ['sent', 'partial'])
            ->get()
            ->sum('remaining_balance');

        $totalHutang = (float) PurchaseOrder::query()
            ->whereIn('status', ['sent', 'partial'])
            ->get()
            ->sum('grand_total');

        $baseCardClass = implode(' ', [
            'min-h-[132px]',
            'rounded-2xl',
            'border',
            'border-gray-200',
            'bg-white',
            'shadow-sm',
            'ring-1',
            'ring-gray-950/5',
            'transition',
            'hover:-translate-y-0.5',
            'hover:shadow-md',
            'dark:border-gray-800',
            'dark:bg-gray-900',
            'dark:ring-white/10',
        ]);

        return [
            Stat::make('Periode', Carbon::now()->translatedFormat('F Y'))
                ->description('Ringkasan bulan berjalan')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Saldo Kas Tersedia', $this->formatRupiah($saldoKas))
                ->description('Total pemasukan - pengeluaran')
                ->descriptionIcon($saldoKas >= 0 ? 'heroicon-m-wallet' : 'heroicon-m-exclamation-triangle')
                ->color($saldoKas >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Pemasukan Bulan Ini', $this->formatRupiah($pemasukanBulanIni))
                ->description('Cash inflow bulan berjalan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Pengeluaran Bulan Ini', $this->formatRupiah($pengeluaranBulanIni))
                ->description('Cash outflow bulan berjalan')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Piutang Belum Lunas', $this->formatRupiah($totalPiutang))
                ->description('Tagihan customer belum lunas')
                ->descriptionIcon('heroicon-m-document-currency-dollar')
                ->color('warning')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Hutang Belum Lunas', $this->formatRupiah($totalHutang))
                ->description('Tagihan supplier belum lunas')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('danger')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Laba / Rugi Bulan Ini', $this->formatRupiah($labaBersihBulanIni))
                ->description($labaBersihBulanIni >= 0 ? 'Bulan ini profit' : 'Bulan ini rugi')
                ->descriptionIcon($labaBersihBulanIni >= 0 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-circle')
                ->color($labaBersihBulanIni >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),

            Stat::make('Transaksi Bulan Ini', number_format($jumlahTransaksiBulanIni, 0, ',', '.'))
                ->description('Total catatan finance bulan ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->extraAttributes([
                    'class' => $baseCardClass,
                ]),
        ];
    }
}
