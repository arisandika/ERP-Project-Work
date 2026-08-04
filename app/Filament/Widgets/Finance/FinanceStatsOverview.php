<?php
namespace App\Filament\Widgets\Finance;

use App\Services\Finance\FinancialService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getStats(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);

        $summary = app(FinancialService::class)->getSummaryForYear($year);

        $isCurrentYear = $year === now()->year;
        $periodLabel   = $isCurrentYear ? "tahun berjalan ({$year})" : "sepanjang tahun {$year}";

        return [
            Stat::make("Pemasukan ({$year})", 'Rp ' . number_format($summary['income'], 0, ',', '.'))
                ->description('Total pendapatan kotor')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('success')
                ->chart([10, 15, 12, 18, 14, 20, round($summary['income'] / 12000000)]),

            Stat::make("Pengeluaran ({$year})", 'Rp ' . number_format($summary['expense'], 0, ',', '.'))
                ->description('Total biaya operasional')
                ->descriptionIcon('heroicon-m-arrow-down-left')
                ->color('danger')
                ->chart([8, 10, 7, 12, 9, 11, round($summary['expense'] / 12000000)]),

            Stat::make("Net Profit ({$year})", 'Rp ' . number_format($summary['net_profit'], 0, ',', '.'))
                ->description('Laba bersih periode')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($summary['net_profit'] >= 0 ? 'success' : 'danger'),

            Stat::make('Sisa Piutang', 'Rp ' . number_format($summary['receivable_remaining'], 0, ',', '.'))
                ->description('Invoice belum diterima')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Sisa Utang', 'Rp ' . number_format($summary['payable_remaining'], 0, ',', '.'))
                ->description('PO belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('Saldo Kas', 'Rp ' . number_format($summary['ending_balance'], 0, ',', '.'))
                ->description('Saldo akhir kas')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
