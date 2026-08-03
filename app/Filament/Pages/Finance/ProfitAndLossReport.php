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
use Illuminate\Support\Collection;

class ProfitAndLossReport extends Page implements HasForms
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
        $now = Carbon::now();
        $this->form->fill([
            'period_type' => 'this_month',
            'start_date' => $now->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->endOfMonth()->format('Y-m-d'),
            'compare_mode' => 'none',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period_type')
                    ->label('Periode')
                    ->options([
                        'custom'       => 'Custom',
                        'this_month'   => 'Bulan Ini',
                        'last_month'   => 'Bulan Lalu',
                        'this_quarter' => 'Kuartal Ini',
                        'last_quarter' => 'Kuartal Lalu',
                        'this_year'    => 'Tahun Ini',
                        'last_year'    => 'Tahun Lalu',
                    ])
                    ->default('this_month')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state === 'custom') return;

                        $dates = $this->resolveDatesForType($state);
                        $set('start_date', $dates[0]->format('Y-m-d'));
                        $set('end_date', $dates[1]->format('Y-m-d'));
                    })
                    ->native(false),

                DatePicker::make('start_date')
                    ->label('Dari Tanggal')
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

                Select::make('compare_mode')
                    ->label('Bandingkan Dengan')
                    ->options([
                        'none'             => 'Tidak Ada Perbandingan',
                        'previous_period'  => 'Periode Sebelumnya',
                        'last_year'        => 'Tahun Lalu',
                    ])
                    ->default('none')
                    ->live()
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(4);
    }

    protected function getViewData(): array
    {
        [$startDate, $endDate] = $this->resolveDates();

        $aggregates = $this->queryAggregates($startDate, $endDate);
        $revenueDetails = $this->extractDetails($aggregates, 'revenue');
        $cogsDetails = $this->extractDetails($aggregates, 'cogs');
        $opexDetails = $this->extractDetails($aggregates, 'operating_expense');
        $otherIncomeDetails = $this->extractDetails($aggregates, 'other_income');
        $otherExpenseDetails = $this->extractDetails($aggregates, 'other_expense');

        $totalRevenue = $revenueDetails->flatten(1)->sum('amount');
        $totalCogs = $cogsDetails->flatten(1)->sum('amount');
        $grossProfit = $totalRevenue - $totalCogs;
        $totalOpex = $opexDetails->flatten(1)->sum('amount');
        $operatingProfit = $grossProfit - $totalOpex;
        $totalOtherIncome = $otherIncomeDetails->flatten(1)->sum('amount');
        $totalOtherExpense = $otherExpenseDetails->flatten(1)->sum('amount');
        $profitBeforeTax = $operatingProfit + $totalOtherIncome - $totalOtherExpense;
        $taxExpense = 0;
        $netProfit = $profitBeforeTax - $taxExpense;

        // Comparison period
        $compareData = null;
        $compareMode = $this->data['compare_mode'] ?? 'none';
        if ($compareMode !== 'none') {
            [$compStart, $compEnd] = $this->resolveComparisonDates($startDate, $endDate, $compareMode);

            $compAggregates = $this->queryAggregates($compStart, $compEnd);
            $compRevenue = $this->extractDetails($compAggregates, 'revenue')->flatten(1)->sum('amount');
            $compCogs = $this->extractDetails($compAggregates, 'cogs')->flatten(1)->sum('amount');
            $compOpex = $this->extractDetails($compAggregates, 'operating_expense')->flatten(1)->sum('amount');
            $compOtherIncome = $this->extractDetails($compAggregates, 'other_income')->flatten(1)->sum('amount');
            $compOtherExpense = $this->extractDetails($compAggregates, 'other_expense')->flatten(1)->sum('amount');

            $compGrossProfit = $compRevenue - $compCogs;
            $compOperatingProfit = $compGrossProfit - $compOpex;
            $compProfitBeforeTax = $compOperatingProfit + $compOtherIncome - $compOtherExpense;

            $compareData = [
                'period'         => $compStart->format('d M Y') . ' - ' . $compEnd->format('d M Y'),
                'totalRevenue'   => $compRevenue,
                'totalCogs'      => $compCogs,
                'grossProfit'    => $compGrossProfit,
                'totalOpex'      => $compOpex,
                'operatingProfit'=> $compOperatingProfit,
                'totalOtherIncome'  => $compOtherIncome,
                'totalOtherExpense' => $compOtherExpense,
                'profitBeforeTax'   => $compProfitBeforeTax,
                'taxExpense'     => 0,
                'netProfit'      => $compProfitBeforeTax,
            ];
        }

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'compareData' => $compareData,

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

    private function resolveDates(): array
    {
        return [
            Carbon::parse($this->data['start_date'] ?? Carbon::now()->startOfMonth())->startOfDay(),
            Carbon::parse($this->data['end_date'] ?? Carbon::now()->endOfMonth())->endOfDay(),
        ];
    }

    private function resolveDatesForType(string $type): array
    {
        $now = Carbon::now();

        return match ($type) {
            'this_month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'this_year'    => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year'    => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default        => [
                Carbon::parse($this->data['start_date'] ?? $now->startOfMonth())->startOfDay(),
                Carbon::parse($this->data['end_date'] ?? $now->endOfMonth())->endOfDay(),
            ],
        };
    }

    private function resolveComparisonDates(Carbon $start, Carbon $end, string $mode): array
    {
        if ($mode === 'last_year') {
            return [$start->copy()->subYear(), $end->copy()->subYear()];
        }

        // previous_period: shift backward by same duration
        $duration = $start->diffInDays($end);
        return [
            $start->copy()->subDays($duration + 1),
            $end->copy()->subDays($duration + 1),
        ];
    }

    private function queryAggregates(Carbon $startDate, Carbon $endDate): Collection
    {
        return FinancialRecord::query()
            ->selectRaw('account_type, COALESCE(category, "Lain-lain") as category_name, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('account_type', ['revenue', 'cogs', 'operating_expense', 'other_income', 'other_expense'])
            ->groupBy('account_type', 'category_name')
            ->get();
    }

    private function extractDetails(Collection $aggregates, string $accountType): Collection
    {
        return $aggregates
            ->where('account_type', $accountType)
            ->map(fn ($item) => [
                'category' => $item->category_name,
                'amount'   => (float) $item->total_amount,
            ])
            ->groupBy('category');
    }
}
