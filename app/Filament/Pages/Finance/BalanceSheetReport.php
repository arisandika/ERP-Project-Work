<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\FinancialRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class BalanceSheetReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Laporan Neraca';
    protected static ?string $slug = 'finance/balance-sheet';

    protected static string $view = 'filament.pages.finance.balance-sheet-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'as_of_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('as_of_date')
                    ->label('Per Tanggal')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
            ])
            ->statePath('data')
            ->columns(1);
    }

    protected function getViewData(): array
    {
        $asOfDateStr = $this->data['as_of_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $asOfDate = Carbon::parse($asOfDateStr)->endOfDay();

        $records = FinancialRecord::query()
            ->where('transaction_date', '<=', $asOfDate)
            ->get();

        /*
         * Catatan:
         * Neraca idealnya menarik data dari kas, bank, piutang, hutang,
         * inventory, aset tetap, modal, dan laba ditahan.
         *
         * Untuk tahap awal, kita hitung dari FinancialRecord.
         */

        $cashBalance = $this->calculateCashBalance($records);

        $assetRecords = $records->where('account_type', 'asset');
        $liabilityRecords = $records->where('account_type', 'liability');
        $equityRecords = $records->where('account_type', 'equity');

        $assets = $this->groupAccountDetails($assetRecords);
        $liabilities = $this->groupAccountDetails($liabilityRecords);
        $equities = $this->groupAccountDetails($equityRecords);

        $totalAssetsFromRecords = $assetRecords->sum('amount');
        $totalLiabilities = $liabilityRecords->sum('amount');
        $totalEquityFromRecords = $equityRecords->sum('amount');

        $currentYearStart = $asOfDate->copy()->startOfYear();

        $currentYearProfit = $this->calculateProfit(
            $currentYearStart,
            $asOfDate
        );

        /*
         * Supaya kas muncul sebagai aset utama.
         * Kalau nanti lu punya akun kas/bank sendiri, bagian ini bisa diganti.
         */
        $totalAssets = $cashBalance + $totalAssetsFromRecords;

        /*
         * Ekuitas sementara:
         * modal tercatat + laba berjalan.
         */
        $totalEquity = $totalEquityFromRecords + $currentYearProfit;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $difference = $totalAssets - $totalLiabilitiesAndEquity;

        return [
            'asOfDate' => $asOfDate->format('d M Y'),

            'cashBalance' => $cashBalance,

            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,

            'totalAssetsFromRecords' => $totalAssetsFromRecords,
            'totalAssets' => $totalAssets,

            'totalLiabilities' => $totalLiabilities,

            'totalEquityFromRecords' => $totalEquityFromRecords,
            'currentYearProfit' => $currentYearProfit,
            'totalEquity' => $totalEquity,

            'totalLiabilitiesAndEquity' => $totalLiabilitiesAndEquity,
            'difference' => $difference,
        ];
    }

    private function calculateCashBalance($records): float
    {
        $cashIn = $records
            ->whereIn('type', ['pemasukan', 'piutang'])
            ->sum('amount');

        $cashOut = $records
            ->whereIn('type', ['pengeluaran', 'hutang'])
            ->sum('amount');

        return (float) $cashIn - (float) $cashOut;
    }

    private function groupAccountDetails($records): array
    {
        return $records
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category ?: 'Lain-lain',
                    'amount' => (float) $items->sum('amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $transactions = FinancialRecord::query()
            ->whereBetween('transaction_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->get();

        $totalRevenue = $transactions->where('account_type', 'revenue')->sum('amount');
        $totalCogs = $transactions->where('account_type', 'cogs')->sum('amount');
        $totalOpex = $transactions->where('account_type', 'operating_expense')->sum('amount');
        $totalOtherIncome = $transactions->where('account_type', 'other_income')->sum('amount');
        $totalOtherExpense = $transactions->where('account_type', 'other_expense')->sum('amount');

        return (float) $totalRevenue
            - (float) $totalCogs
            - (float) $totalOpex
            + (float) $totalOtherIncome
            - (float) $totalOtherExpense;
    }
}
