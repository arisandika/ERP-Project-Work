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

class CashFlowReport extends Page implements HasForms
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
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $title = 'Cash Flow Report';
    protected static ?string $navigationLabel = 'Cash Flow';
    protected static ?string $slug = 'finance/cash-flow';

    protected static string $view = 'filament.pages.finance.cash-flow-report';

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

        $openingBalance = $this->calculateOpeningBalance($startDate);
        $operatingDetails = $this->buildCashFlowDetails('operating', $startDate, $endDate);
        $investingDetails = $this->buildCashFlowDetails('investing', $startDate, $endDate);
        $financingDetails = $this->buildCashFlowDetails('financing', $startDate, $endDate);
        $totalOperatingCashFlow = $this->calculateNetCashByActivity('operating', $startDate, $endDate);
        $totalInvestingCashFlow = $this->calculateNetCashByActivity('investing', $startDate, $endDate);
        $totalFinancingCashFlow = $this->calculateNetCashByActivity('financing', $startDate, $endDate);
        $netCashFlow = $totalOperatingCashFlow + $totalInvestingCashFlow + $totalFinancingCashFlow;
        $endingBalance = $openingBalance + $netCashFlow;

        // Comparison period
        $compareData = null;
        $compareMode = $this->data['compare_mode'] ?? 'none';
        if ($compareMode !== 'none') {
            [$compStart, $compEnd] = $this->resolveComparisonDates($startDate, $endDate, $compareMode);
            $compOpeningBalance = $this->calculateOpeningBalance($compStart);
            $compTotalOperating = $this->calculateNetCashByActivity('operating', $compStart, $compEnd);
            $compTotalInvesting = $this->calculateNetCashByActivity('investing', $compStart, $compEnd);
            $compTotalFinancing = $this->calculateNetCashByActivity('financing', $compStart, $compEnd);
            $compNetCashFlow = $compTotalOperating + $compTotalInvesting + $compTotalFinancing;

            $compareData = [
                'period'                => $compStart->format('d M Y') . ' - ' . $compEnd->format('d M Y'),
                'openingBalance'        => $compOpeningBalance,
                'totalOperatingCashFlow'=> $compTotalOperating,
                'totalInvestingCashFlow'=> $compTotalInvesting,
                'totalFinancingCashFlow'=> $compTotalFinancing,
                'netCashFlow'           => $compNetCashFlow,
                'endingBalance'         => $compOpeningBalance + $compNetCashFlow,
            ];
        }

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'compareData' => $compareData,

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

        $duration = $start->diffInDays($end);
        return [
            $start->copy()->subDays($duration + 1),
            $end->copy()->subDays($duration + 1),
        ];
    }

    private function calculateOpeningBalance(Carbon $startDate): float
    {
        $cashIn = (float) FinancialRecord::query()
            ->where('transaction_date', '<', $startDate)
            ->whereIn('type', ['pemasukan', 'piutang'])
            ->sum('amount');

        $cashOut = (float) FinancialRecord::query()
            ->where('transaction_date', '<', $startDate)
            ->whereIn('type', ['pengeluaran', 'hutang'])
            ->sum('amount');

        return $cashIn - $cashOut;
    }

    private function buildCashFlowDetails(string $activityType, Carbon $startDate, Carbon $endDate): array
    {
        return FinancialRecord::query()
            ->selectRaw('
                COALESCE(category, "Lain-lain") as category_name,
                SUM(CASE WHEN type IN ("pemasukan", "piutang") THEN amount ELSE 0 END) as cash_in,
                SUM(CASE WHEN type IN ("pengeluaran", "hutang") THEN amount ELSE 0 END) as cash_out
            ')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('cash_flow_activity', $activityType)
            ->groupBy('category_name')
            ->get()
            ->map(function ($item) {
                $in = (float) $item->cash_in;
                $out = (float) $item->cash_out;

                return [
                    'category' => $item->category_name,
                    'cash_in'  => $in,
                    'cash_out' => $out,
                    'net'      => $in - $out,
                ];
            })
            ->toArray();
    }

    private function calculateNetCashByActivity(string $activityType, Carbon $startDate, Carbon $endDate): float
    {
        $cashIn = (float) FinancialRecord::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('cash_flow_activity', $activityType)
            ->whereIn('type', ['pemasukan', 'piutang'])
            ->sum('amount');

        $cashOut = (float) FinancialRecord::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('cash_flow_activity', $activityType)
            ->whereIn('type', ['pengeluaran', 'hutang'])
            ->sum('amount');

        return $cashIn - $cashOut;
    }
}
