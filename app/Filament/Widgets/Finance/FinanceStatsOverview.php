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

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);

        $summary = app(FinancialService::class)->getSummaryForYear($year);

        // Label periode konsisten dipakai di semua deskripsi,
        // otomatis mengikuti tahun yang dipilih di filter (bukan hardcode "tahun ini").
        $isCurrentYear = $year === now()->year;
        $periodLabel   = $isCurrentYear ? "tahun berjalan ({$year})" : "sepanjang tahun {$year}";

        return [
            Stat::make("Pemasukan ({$year})", 'Rp ' . number_format($summary['income'], 0, ',', '.'))
                ->description("Total pemasukan {$periodLabel}")
                ->color('success'),

            Stat::make("Pengeluaran ({$year})", 'Rp ' . number_format($summary['expense'], 0, ',', '.'))
                ->description("Total pengeluaran {$periodLabel}")
                ->color('danger'),

            Stat::make("Net Profit ({$year})", 'Rp ' . number_format($summary['net_profit'], 0, ',', '.'))
                ->description(
                    $summary['net_profit'] >= 0
                        ? "Laba bersih {$periodLabel}"
                        : "Rugi bersih {$periodLabel}"
                )
                ->color($summary['net_profit'] >= 0 ? 'success' : 'danger'),

            Stat::make('Sisa Piutang (Receivables)', 'Rp ' . number_format($summary['receivable_remaining'], 0, ',', '.'))
                ->description("Belum tertagih hingga akhir {$year}")
                ->color('warning'),

            Stat::make('Sisa Utang (Payables)', 'Rp ' . number_format($summary['payable_remaining'], 0, ',', '.'))
                ->description("Belum dibayar hingga akhir {$year}")
                ->color('warning'),

            Stat::make('Saldo Akhir Kas', 'Rp ' . number_format($summary['ending_balance'], 0, ',', '.'))
                ->description(
                    $isCurrentYear
                        ? 'Posisi kas sampai hari ini'
                        : "Posisi kas kumulatif s.d. 31 Des {$year}"
                )
                ->color('primary'),
        ];
    }
}
