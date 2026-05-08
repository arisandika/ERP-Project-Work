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

class CashFlowReport extends Page implements HasForms
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

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Laporan Arus Kas';
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

        // REFAKTORISASI: Perhitungan langsung dilakukan oleh SQL, memori PHP aman.
        $openingBalance = $this->calculateOpeningBalance($startDate);

        $operatingDetails = $this->buildCashFlowDetails('operating', $startDate, $endDate);
        $investingDetails = $this->buildCashFlowDetails('investing', $startDate, $endDate);
        $financingDetails = $this->buildCashFlowDetails('financing', $startDate, $endDate);

        $totalOperatingCashFlow = $this->calculateNetCashByActivity('operating', $startDate, $endDate);
        $totalInvestingCashFlow = $this->calculateNetCashByActivity('investing', $startDate, $endDate);
        $totalFinancingCashFlow = $this->calculateNetCashByActivity('financing', $startDate, $endDate);

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

    /**
     * Menghitung saldo awal kas dengan query builder tunggal
     */
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

    /**
     * Membangun detail arus kas menggunakan metode Agregasi Bersyarat (Conditional Aggregation) SQL
     */
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
                    'cash_in' => $in,
                    'cash_out' => $out,
                    'net' => $in - $out,
                ];
            })
            ->toArray();
    }

    /**
     * Menghitung net kas per aktivitas secara efisien
     */
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
