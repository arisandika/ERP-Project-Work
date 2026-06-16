<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Finance\FinanceStatsOverview;
use App\Filament\Widgets\Finance\LatestUnpaidInvoices;
use App\Filament\Widgets\Finance\LatestUnpaidPurchaseOrders;
use App\Filament\Widgets\Finance\ReimburseApprovalOverview;
use App\Filament\Widgets\Finance\ReimburseRequestOverview;
use App\Services\Finance\FinancialService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardFinance extends BaseDashboard
{
    use HasPageShield, BelongsToModule, HasFiltersForm {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Finance Dashboard';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function getSubheading(): ?string
    {
        return 'Financial overview, receivables, payables, and reimbursement activities.';
    }

    /**
     * Filter Global di atas Dashboard (Otomatis dibaca semua Widget)
     */
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('year')
                            ->label('Tahun Analisis')
                            ->options(fn (FinancialService $service) => $service->getYearOptions())
                            ->default(now()->year)
                            ->selectablePlaceholder(false),
                    ])
                    ->columns(4) // Menjaga form tetap ramping di atas
            ]);
    }

    /**
     * Daftarkan semua widget yang mau ditampilkan
     */
    public function getWidgets(): array
    {
        return [
            FinanceStatsOverview::class,
            ReimburseApprovalOverview::class,
            ReimburseRequestOverview::class,
            LatestUnpaidInvoices::class,
            LatestUnpaidPurchaseOrders::class,
        ];
    }
}
