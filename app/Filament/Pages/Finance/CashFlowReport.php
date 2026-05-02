<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\FinancialRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class CashFlowReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Laporan Arus Kas';
    protected static ?string $slug = 'finance/cash-flow';

    protected static string $view = 'filament.pages.finance.cash-flow-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Periode Dari')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),

                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    protected function getViewData(): array
    {
        $startDateStr = $this->data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDateStr = $this->data['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        /*
         * Saldo awal kas:
         * semua transaksi sebelum periode.
         *
         * pemasukan/piutang dianggap menambah kas
         * pengeluaran/hutang dianggap mengurangi kas
         *
         * Catatan:
         * Ini masih pendekatan cash-basis dari FinancialRecord.
         */
        $openingRecords = FinancialRecord::query()
            ->where('transaction_date', '<', $startDate)
            ->get();

        $openingBalance = $this->calculateNetCash($openingRecords);

        $transactions = FinancialRecord::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $operatingTransactions = $transactions
            ->where('cash_flow_activity', 'operating');

        $investingTransactions = $transactions
            ->where('cash_flow_activity', 'investing');

        $financingTransactions = $transactions
            ->where('cash_flow_activity', 'financing');

        $operatingDetails = $this->buildCashFlowDetails($operatingTransactions);
        $investingDetails = $this->buildCashFlowDetails($investingTransactions);
        $financingDetails = $this->buildCashFlowDetails($financingTransactions);

        $totalOperatingCashFlow = $this->calculateNetCash($operatingTransactions);
        $totalInvestingCashFlow = $this->calculateNetCash($investingTransactions);
        $totalFinancingCashFlow = $this->calculateNetCash($financingTransactions);

        $netCashFlow = $totalOperatingCashFlow + $totalInvestingCashFlow + $totalFinancingCashFlow;
        $endingBalance = $openingBalance + $netCashFlow;

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),

            'openingBalance' => $openingBalance,

            'operatingDetails' => $operatingDetails,
            'investingDetails' => $investingDetails,
            'financingDetails' => $financingDetails,

            'totalOperatingCashFlow' => $totalOperatingCashFlow,
            'totalInvestingCashFlow' => $totalInvestingCashFlow,
            'totalFinancingCashFlow' => $totalFinancingCashFlow,

            'netCashFlow' => $netCashFlow,
            'endingBalance' => $endingBalance,
        ];
    }

    private function calculateNetCash($records): float
    {
        $cashInTypes = ['pemasukan', 'piutang'];
        $cashOutTypes = ['pengeluaran', 'hutang'];

        $cashIn = $records
            ->whereIn('type', $cashInTypes)
            ->sum('amount');

        $cashOut = $records
            ->whereIn('type', $cashOutTypes)
            ->sum('amount');

        return (float) $cashIn - (float) $cashOut;
    }

    private function buildCashFlowDetails($records): array
    {
        return $records
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category ?: 'Lain-lain',
                    'cash_in' => $items->whereIn('type', ['pemasukan', 'piutang'])->sum('amount'),
                    'cash_out' => $items->whereIn('type', ['pengeluaran', 'hutang'])->sum('amount'),
                    'net' => $this->calculateNetCash($items),
                ];
            })
            ->values()
            ->toArray();
    }
}
