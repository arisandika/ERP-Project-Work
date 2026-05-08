<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Finance\FinancialRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheetReport extends Page implements HasForms
{
    use InteractsWithForms;

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

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Laporan Neraca';
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

        // REFAKTORISASI: Tidak ada lagi penarikan seluruh data ke memori PHP.
        // Semua dihitung langsung oleh Database.
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

    /**
     * Hitung saldo kas langsung menggunakan Query Builder
     */
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

    /**
     * Grouping kategori akun menggunakan SQL GROUP BY
     */
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

    /**
     * Hitung total per tipe akun dengan satu query SUM
     */
    private function getTotalByAccountType(string $accountType, Carbon $asOfDate): float
    {
        return (float) FinancialRecord::query()
            ->where('transaction_date', '<=', $asOfDate)
            ->where('account_type', $accountType)
            ->sum('amount');
    }

    /**
     * Hitung profit menggunakan agregasi SQL terpadu
     */
    private function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        // Tarik rekap total berdasarkan account_type dalam satu query
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
