<?php
namespace App\Filament\Pages\Sales;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Sales\InvoiceReportTable;
use App\Filament\Widgets\Sales\SalesSummaryStats;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardSales extends BaseDashboard
{
    use HasFiltersForm;
    /**
     * Resolusi Konflik Trait untuk Keamanan & Multi-Tenant
     * Mencegah akses ke Dashboard jika Modul Sales tidak aktif atau User tidak memiliki hak.
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'sales';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.sales.dashboard-sales';

    protected static ?string $slug = 'sales/dashboard';

    protected static string $routePath = 'sales/dashboard';

    protected static ?string $navigationLabel = 'Dashboard Sales';

    protected static ?string $title = 'Dashboard Sales';

    // public function mount(): void
    // {
    //     abort_unless(static::canAccess(), 403);
    // }

    /**
     * Override method canAccess()
     */
    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    /**
     * Override method shouldRegisterNavigation()
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Analitik')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Awal')
                            ->default(now()->startOfMonth())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live(),
                    ])
                    ->columns(['default' => 12, 'md' => 2]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            SalesSummaryStats::class,
            // RevenueChart::class,
            InvoiceReportTable::class,
        ];
    }
}
