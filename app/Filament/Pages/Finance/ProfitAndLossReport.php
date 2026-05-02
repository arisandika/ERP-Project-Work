<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\FinancialRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ProfitAndLossReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Laporan Laba Rugi';
    protected static ?string $slug = 'finance/profit-and-loss';

    protected static string $view = 'filament.pages.finance.profit-and-loss-report';

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

        $transactions = FinancialRecord::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $revenueTransactions = $transactions->where('account_type', 'revenue');
        $cogsTransactions = $transactions->where('account_type', 'cogs');
        $opexTransactions = $transactions->where('account_type', 'operating_expense');
        $otherIncomeTransactions = $transactions->where('account_type', 'other_income');
        $otherExpenseTransactions = $transactions->where('account_type', 'other_expense');

        $revenueDetails = $revenueTransactions->groupBy('category');
        $cogsDetails = $cogsTransactions->groupBy('category');
        $opexDetails = $opexTransactions->groupBy('category');
        $otherIncomeDetails = $otherIncomeTransactions->groupBy('category');
        $otherExpenseDetails = $otherExpenseTransactions->groupBy('category');

        $totalRevenue = $revenueTransactions->sum('amount');
        $totalCogs = $cogsTransactions->sum('amount');
        $grossProfit = $totalRevenue - $totalCogs;

        $totalOpex = $opexTransactions->sum('amount');
        $operatingProfit = $grossProfit - $totalOpex;

        $totalOtherIncome = $otherIncomeTransactions->sum('amount');
        $totalOtherExpense = $otherExpenseTransactions->sum('amount');

        $profitBeforeTax = $operatingProfit + $totalOtherIncome - $totalOtherExpense;

        // Untuk sekarang pajak belum dihitung dari modul khusus, jadi diset 0 dulu.
        $taxExpense = 0;

        $netProfit = $profitBeforeTax - $taxExpense;

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),

            'revenueDetails' => $revenueDetails,
            'cogsDetails' => $cogsDetails,
            'opexDetails' => $opexDetails,
            'otherIncomeDetails' => $otherIncomeDetails,
            'otherExpenseDetails' => $otherExpenseDetails,

            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,

            'totalOpex' => $totalOpex,
            'operatingProfit' => $operatingProfit,

            'totalOtherIncome' => $totalOtherIncome,
            'totalOtherExpense' => $totalOtherExpense,

            'profitBeforeTax' => $profitBeforeTax,
            'taxExpense' => $taxExpense,
            'netProfit' => $netProfit,
        ];
    }
}
