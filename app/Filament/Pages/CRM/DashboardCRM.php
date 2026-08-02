<?php
namespace App\Filament\Pages\CRM;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\CRM\CRMStatsOverview;
use App\Filament\Widgets\CRM\DealPipelineChart;
use App\Filament\Widgets\CRM\RecentLeadsTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardCRM extends Dashboard
{
    use BelongsToModule, HasFiltersForm, HasPageShield {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'crm';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard CRM';

    protected static ?string $slug = 'crm/dashboard';

    protected static string $routePath = 'crm/dashboard';

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
        return 'Kelola leads, deals, dan pelanggan Anda.';
    }

    public function getWidgets(): array
    {
        return [
            CRMStatsOverview::class,
            DealPipelineChart::class,
            RecentLeadsTable::class,
        ];
    }
}
