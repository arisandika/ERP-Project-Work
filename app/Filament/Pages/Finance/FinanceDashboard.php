<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Resources\Finance\FinancialRecordResource\Widgets\FinanceOverview;
use App\Filament\Widgets\Finance\LatestUnpaidInvoices;
use App\Filament\Widgets\Finance\LatestUnpaidPurchaseOrders;
use App\Models\Finance\FinancialRecord;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dashboard Keuangan';

    protected static ?string $slug = 'finance/dashboard';

    protected static string $view = 'filament.pages.finance.finance-dashboard';

    public string $summaryMode = 'monthly';

    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = now()->year;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceOverview::class,
            LatestUnpaidInvoices::class,
            LatestUnpaidPurchaseOrders::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }

    public function setSummaryMode(string $mode): void
    {
        if (! in_array($mode, ['monthly', 'yearly'], true)) {
            return;
        }

        $this->summaryMode = $mode;
    }

    public function formatMoney(float|int|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    public function getYearOptionsProperty(): Collection
    {
        $years = FinancialRecord::query()
            ->selectRaw('YEAR(transaction_date) as year')
            ->whereNotNull('transaction_date')
            ->groupByRaw('YEAR(transaction_date)')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter();

        if ($years->isEmpty()) {
            return collect([now()->year]);
        }

        if (! $years->contains(now()->year)) {
            $years->prepend(now()->year);
        }

        return $years->unique()->values();
    }

    public function getCurrentPeriodLabelProperty(): string
    {
        return Carbon::create($this->selectedYear, now()->month, 1)
            ->translatedFormat('F Y');
    }

    public function getSelectedYearSummaryProperty(): array
    {
        $date = Carbon::create($this->selectedYear, 1, 1);

        return $this->calculateSummary(
            $date->copy()->startOfYear(),
            $date->copy()->endOfYear()
        );
    }

    public function getCurrentMonthSummaryProperty(): array
    {
        $date = Carbon::create($this->selectedYear, now()->month, 1);

        return $this->calculateSummary(
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth()
        );
    }

    public function getMonthlySummariesProperty(): Collection
    {
        $endMonth = now()->month;

        return collect(range(1, $endMonth))
            ->map(function (int $month) {
                $date = Carbon::create($this->selectedYear, $month, 1);

                $summary = $this->calculateSummary(
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth()
                );

                return array_merge($summary, [
                    'period' => $date->translatedFormat('F Y'),
                    'sort' => $date->format('Ym'),
                ]);
            })
            ->sortBy('sort')
            ->values();
    }
    
    public function getYearlySummariesProperty(): Collection
    {
        return $this->yearOptions
            ->map(function ($year) {
                $date = Carbon::create((int) $year, 1, 1);

                $summary = $this->calculateSummary(
                    $date->copy()->startOfYear(),
                    $date->copy()->endOfYear()
                );

                return array_merge($summary, [
                    'period' => (string) $year,
                    'year' => (int) $year,
                ]);
            })
            ->values();
    }

    protected function calculateSummary(Carbon $startDate, Carbon $endDate): array
    {
        $income = (float) FinancialRecord::query()
            ->where('type', 'pemasukan')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $expense = (float) FinancialRecord::query()
            ->where('type', 'pengeluaran')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $receivableTotal = (float) FinancialRecord::query()
            ->where('type', 'piutang')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $receivablePaid = (float) FinancialRecord::query()
            ->where('type', 'pemasukan')
            ->where('category', 'Accounts Receivable')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $payableTotal = (float) FinancialRecord::query()
            ->where('type', 'hutang')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $payablePaid = (float) FinancialRecord::query()
            ->where('type', 'pengeluaran')
            ->where('category', 'Accounts Payable')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $cashInUntilEndDate = (float) FinancialRecord::query()
            ->where('type', 'pemasukan')
            ->where('transaction_date', '<=', $endDate)
            ->sum('amount');

        $cashOutUntilEndDate = (float) FinancialRecord::query()
            ->where('type', 'pengeluaran')
            ->where('transaction_date', '<=', $endDate)
            ->sum('amount');

        $netProfit = $income - $expense;
        $endingBalance = $cashInUntilEndDate - $cashOutUntilEndDate;

        return [
            'income' => $income,
            'expense' => $expense,
            'net_profit' => $netProfit,
            'receivable_total' => $receivableTotal,
            'receivable_paid' => $receivablePaid,
            'receivable_remaining' => max($receivableTotal - $receivablePaid, 0),
            'payable_total' => $payableTotal,
            'payable_paid' => $payablePaid,
            'payable_remaining' => max($payableTotal - $payablePaid, 0),
            'ending_balance' => $endingBalance,
        ];
    }
}
