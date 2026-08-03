<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Finance\FinanceStatsOverview;
use App\Filament\Widgets\Finance\LatestUnpaidInvoices;
use App\Filament\Widgets\Finance\LatestUnpaidPurchaseOrders;
use App\Filament\Widgets\Finance\ReimburseApprovalOverview;
use App\Services\Finance\FinancialService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardFinance extends BaseDashboard
{
    use BelongsToModule;

    protected static ?string $module = 'finance';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.finance.dashboard-finance';

    protected static ?string $slug = 'finance/dashboard';

    protected static string $routePath = 'finance/dashboard';

    protected static ?string $navigationLabel = 'Dashboard Finance';

    protected static ?string $title = 'Dashboard Finance';
    
    protected static ?int $navigationSort = 1;

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
            LatestUnpaidInvoices::class,
            LatestUnpaidPurchaseOrders::class,
        ];
    }
}
