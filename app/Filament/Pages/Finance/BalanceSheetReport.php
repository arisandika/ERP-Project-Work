<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Finance\FinancialRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class BalanceSheetReport extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $title = 'Balance Sheet';
    protected static ?string $navigationLabel = 'Balance Sheet';
    protected static ?string $slug = 'finance/balance-sheet';
    protected static string $view = 'filament.pages.finance.balance-sheet-report';

    public ?array $data = [];

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
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'period_type' => 'this_month',
            'as_of_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period_type')
                    ->label('Periode')
                    ->options([
                        'custom' => 'Custom',
                        'this_month' => 'Akhir Bulan Ini',
                        'last_month' => 'Akhir Bulan Lalu',
                        'this_quarter' => 'Akhir Kuartal Ini',
                        'last_quarter' => 'Akhir Kuartal Lalu',
                        'this_year' => 'Akhir Tahun Ini',
                        'last_year' => 'Akhir Tahun Lalu',
                    ])
                    ->default('this_month')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state === 'custom')
                            return;

                        $date = $this->resolveDateForType($state);
                        $set('as_of_date', $date->format('Y-m-d'));
                    })
                    ->native(false),
                DatePicker::make('as_of_date')
                    ->label('Tanggal')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
            ])
            ->statePath('data')
            ->columns(['default' => 12, 'md' => 2]);
    }

    protected function getViewData(): array
    {
        $asOfDate = $this->resolveDate();

        $cashBalance = $this->calculateCashBalance($asOfDate);
        $assets = $this->getGroupedAccounts('asset', $asOfDate);
        $liabilities = $this->getGroupedAccounts('liability', $asOfDate);
        $equities = $this->getGroupedAccounts('equity', $asOfDate);
        $totalAssetsFromRecords = $this->getTotalByAccountType('asset', $asOfDate);
        $totalLiabilities = $this->getTotalByAccountType('liability', $asOfDate);
        $totalEquityFromRecords = $this->getTotalByAccountType('equity', $asOfDate);
        $currentYearStart = $asOfDate->copy()->startOfYear();
        $currentYearProfit = $this->calculateProfit($currentYearStart, $asOfDate);
        $totalAssets = $cashBalance + $totalAssetsFromRecords;
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

    private function resolveDate(): Carbon
    {
        return Carbon::parse($this->data['as_of_date'] ?? Carbon::now()->endOfMonth())->endOfDay();
    }

    private function resolveDateForType(string $type): Carbon
    {
        $now = Carbon::now();

        return match ($type) {
            'this_month' => $now->copy()->endOfMonth(),
            'last_month' => $now->copy()->subMonth()->endOfMonth(),
            'this_quarter' => $now->copy()->endOfQuarter(),
            'last_quarter' => $now->copy()->subQuarter()->endOfQuarter(),
            'this_year' => $now->copy()->endOfYear(),
            'last_year' => $now->copy()->subYear()->endOfYear(),
            default => Carbon::parse($this->data['as_of_date'] ?? $now->endOfMonth())->endOfDay(),
        };
    }

    private function calculateCashBalance(Carbon $asOfDate): float
    {
        $cashIn = (float) FinancialRecord::query()
            ->where('transaction_date', '<=', $asOfDate)
            ->whereIn('type', ['pemasukan', 'piutang'])
            ->sum('amount');

        $cashOut = (float) FinancialRecord::query()
            ->where('transaction_date', '<=', $asOfDate)
            ->whereIn('type', ['pengeluaran', 'hutang'])
            ->sum('amount');

        return $cashIn - $cashOut;
    }

    private function getGroupedAccounts(string $accountType, Carbon $asOfDate): array
    {
        return FinancialRecord::query()
            ->selectRaw('COALESCE(category, "Lain-lain") as category_name, SUM(amount) as total_amount')
            ->where('transaction_date', '<=', $asOfDate)
            ->where('account_type', $accountType)
            ->groupBy('category_name')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category_name,
                'amount' => (float) $item->total_amount,
            ])
            ->toArray();
    }

    private function getTotalByAccountType(string $accountType, Carbon $asOfDate): float
    {
        return (float) FinancialRecord::query()
            ->where('transaction_date', '<=', $asOfDate)
            ->where('account_type', $accountType)
            ->sum('amount');
    }

    private function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $totals = FinancialRecord::query()
            ->selectRaw('account_type, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('account_type', ['revenue', 'cogs', 'operating_expense', 'other_income', 'other_expense'])
            ->groupBy('account_type')
            ->pluck('total_amount', 'account_type');

        $revenue = (float) ($totals['revenue'] ?? 0);
        $cogs = (float) ($totals['cogs'] ?? 0);
        $opex = (float) ($totals['operating_expense'] ?? 0);
        $otherIncome = (float) ($totals['other_income'] ?? 0);
        $otherExpense = (float) ($totals['other_expense'] ?? 0);

        return $revenue - $cogs - $opex + $otherIncome - $otherExpense;
    }
}
