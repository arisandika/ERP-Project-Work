<?php
namespace App\Filament\Pages\Marketing;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Marketing\MarketingStatsOverview;
use App\Filament\Widgets\Marketing\ActivePromoCodesTable;
use App\Filament\Widgets\Marketing\ContentOverviewTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardMarketing extends Dashboard
{
    use BelongsToModule, HasFiltersForm, HasPageShield {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'marketing';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard Marketing';

    protected static ?string $slug = 'marketing/dashboard';

    protected static string $routePath = 'marketing/dashboard';

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
        return 'Kelola konten, promo, dan campaign marketing Anda.';
    }

    public function getWidgets(): array
    {
        return [
            MarketingStatsOverview::class,
            ActivePromoCodesTable::class,
            ContentOverviewTable::class,
        ];
    }
}
