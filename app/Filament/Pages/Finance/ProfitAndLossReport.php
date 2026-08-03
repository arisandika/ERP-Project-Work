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
use Illuminate\Support\Collection;

class ProfitAndLossReport extends Page implements HasForms
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
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Profit & Loss Report';
    protected static ?string $navigationLabel = 'Profit & Loss';
    protected static ?string $slug = 'finance/profit-and-loss';

    protected static string $view = 'filament.pages.finance.profit-and-loss-report';

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

        // REFAKTORISASI: Multi-Column Aggregation.
        // Melakukan 1 Query saja ke database untuk mengambil SEMUA rekapitulasi Laba Rugi.
        $aggregates = FinancialRecord::query()
            ->selectRaw('account_type, COALESCE(category, "Lain-lain") as category_name, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('account_type', ['revenue', 'cogs', 'operating_expense', 'other_income', 'other_expense'])
            ->groupBy('account_type', 'category_name')
            ->get();

        // Memecah hasil Query tunggal ke masing-masing kelompok akun
        $revenueDetails = $this->extractDetails($aggregates, 'revenue');
        $cogsDetails = $this->extractDetails($aggregates, 'cogs');
        $opexDetails = $this->extractDetails($aggregates, 'operating_expense');
        $otherIncomeDetails = $this->extractDetails($aggregates, 'other_income');
        $otherExpenseDetails = $this->extractDetails($aggregates, 'other_expense');

        // Menghitung subtotal
        $totalRevenue = $revenueDetails->flatten(1)->sum('amount');
        $totalCogs = $cogsDetails->flatten(1)->sum('amount');
        $grossProfit = $totalRevenue - $totalCogs;

        $totalOpex = $opexDetails->flatten(1)->sum('amount');
        $operatingProfit = $grossProfit - $totalOpex;

        $totalOtherIncome = $otherIncomeDetails->flatten(1)->sum('amount');
        $totalOtherExpense = $otherExpenseDetails->flatten(1)->sum('amount');

        $profitBeforeTax = $operatingProfit + $totalOtherIncome - $totalOtherExpense;

        // Untuk sekarang pajak belum dihitung dari modul khusus
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

    /**
     * Helper untuk memfilter koleksi agregat berdasarkan tipe akun
     */
    private function extractDetails(Collection $aggregates, string $accountType): Collection
    {
        return $aggregates
            ->where('account_type', $accountType)
            ->map(function ($item) {
                return [
                    'category' => $item->category_name,
                    'amount' => (float) $item->total_amount,
                ];
            })
            ->groupBy('category');
    }
}
