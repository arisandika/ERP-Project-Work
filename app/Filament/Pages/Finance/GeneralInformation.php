<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Finance\FinancialRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GeneralInformation extends Page
{
    /**
     * Resolusi Konflik Trait untuk Keamanan & Multi-Tenant
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance';

    // Mengganti icon agar lebih sesuai dengan "General Info" ketimbang "Dashboard"
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'General Info';

    protected static ?string $slug = 'finance/general-info';

    // PASTIKAN ANDA JUGA MENGUBAH NAMA FILE BLADE MENJADI general-info.blade.php
    protected static string $view = 'filament.pages.finance.general-info';

    public string $summaryMode = 'monthly';

    public int $selectedYear;

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function mount(): void
    {
        $this->selectedYear = now()->year;
    }

    // WIDGET TELAH DIHAPUS SESUAI PERMINTAAN UNTUK FOKUS KE GENERAL INFO

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

    /**
     * REFAKTORISASI: Mengompresi 8 query menjadi 2 query menggunakan SQL Conditional Aggregation.
     */
    protected function calculateSummary(Carbon $startDate, Carbon $endDate): array
    {
        // Query 1: Menghitung semua pemasukan, pengeluaran, hutang, piutang dalam 1 hit
        $periodStats = FinancialRecord::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as expense,
                SUM(CASE WHEN type = 'piutang' THEN amount ELSE 0 END) as receivable_total,
                SUM(CASE WHEN type = 'pemasukan' AND category = 'Accounts Receivable' THEN amount ELSE 0 END) as receivable_paid,
                SUM(CASE WHEN type = 'hutang' THEN amount ELSE 0 END) as payable_total,
                SUM(CASE WHEN type = 'pengeluaran' AND category = 'Accounts Payable' THEN amount ELSE 0 END) as payable_paid
            ")
            ->first();

        // Query 2: Menghitung saldo akhir hingga tanggal tersebut
        $balanceStats = FinancialRecord::query()
            ->where('transaction_date', '<=', $endDate)
            ->selectRaw("
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) as cash_in,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as cash_out
            ")
            ->first();

        $income = (float) $periodStats->income;
        $expense = (float) $periodStats->expense;
        $receivableTotal = (float) $periodStats->receivable_total;
        $receivablePaid = (float) $periodStats->receivable_paid;
        $payableTotal = (float) $periodStats->payable_total;
        $payablePaid = (float) $periodStats->payable_paid;

        $cashInUntilEndDate = (float) $balanceStats->cash_in;
        $cashOutUntilEndDate = (float) $balanceStats->cash_out;

        return [
            'income' => $income,
            'expense' => $expense,
            'net_profit' => $income - $expense,
            'receivable_total' => $receivableTotal,
            'receivable_paid' => $receivablePaid,
            'receivable_remaining' => max($receivableTotal - $receivablePaid, 0),
            'payable_total' => $payableTotal,
            'payable_paid' => $payablePaid,
            'payable_remaining' => max($payableTotal - $payablePaid, 0),
            'ending_balance' => $cashInUntilEndDate - $cashOutUntilEndDate,
        ];
    }
}
