<?php

namespace App\Services\Finance;

use App\Models\Finance\FinancialRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialService
{
    public function getYearOptions(): array
    {
        $years = FinancialRecord::query()
            ->selectRaw('YEAR(transaction_date) as year')
            ->whereNotNull('transaction_date')
            ->groupByRaw('YEAR(transaction_date)')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            return [now()->year => now()->year];
        }

        if (!in_array(now()->year, $years)) {
            array_unshift($years, now()->year);
        }

        return array_combine($years, $years);
    }

    public function getSummaryForYear(int $year): array
    {
        $periodStats = FinancialRecord::query()
            ->whereYear('transaction_date', $year)
            ->selectRaw("
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as expense,
                SUM(CASE WHEN type = 'piutang' THEN amount ELSE 0 END) as receivable_total,
                SUM(CASE WHEN type = 'pemasukan' AND category = 'Accounts Receivable' THEN amount ELSE 0 END) as receivable_paid,
                SUM(CASE WHEN type = 'hutang' THEN amount ELSE 0 END) as payable_total,
                SUM(CASE WHEN type = 'pengeluaran' AND category = 'Accounts Payable' THEN amount ELSE 0 END) as payable_paid
            ")
            ->first();

        $balanceStats = FinancialRecord::query()
            ->where('transaction_date', '<=', Carbon::create($year, 12, 31)->endOfDay())
            ->selectRaw("
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as ending_balance
            ")
            ->first();

        $income = (float) ($periodStats->income ?? 0);
        $expense = (float) ($periodStats->expense ?? 0);
        $receivableTotal = (float) ($periodStats->receivable_total ?? 0);
        $receivablePaid = (float) ($periodStats->receivable_paid ?? 0);
        $payableTotal = (float) ($periodStats->payable_total ?? 0);
        $payablePaid = (float) ($periodStats->payable_paid ?? 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'net_profit' => $income - $expense,
            'receivable_remaining' => max($receivableTotal - $receivablePaid, 0),
            'payable_remaining' => max($payableTotal - $payablePaid, 0),
            'ending_balance' => (float) ($balanceStats->ending_balance ?? 0),
        ];
    }
}
