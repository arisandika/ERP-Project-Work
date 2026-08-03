<?php
namespace App\Filament\Pages\Project;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Project\ProjectStatsOverview;
use App\Filament\Widgets\Project\TicketStatusChart;
use App\Filament\Widgets\Project\RecentTicketsTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardProject extends Dashboard
{
    use BelongsToModule, HasFiltersForm, HasPageShield {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'project';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard Project';

    protected static ?string $title = 'Dashboard Project';

    protected static string $routePath = 'project-dashboard';

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
        return 'Pantau progres project dan tiket tim Anda.';
    }

    public function getWidgets(): array
    {
        return [
            ProjectStatsOverview::class,
            TicketStatusChart::class,
            RecentTicketsTable::class,
        ];
    }
}
