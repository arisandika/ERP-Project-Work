<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\System\RecentUsersTable;
use App\Filament\Widgets\System\SystemStatsOverview;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardSystem extends Dashboard
{
    use BelongsToModule, HasFiltersForm, HasPageShield {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'system';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationLabel = 'Dashboard System';

    protected static ?string $title = 'Dashboard System';

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function getWidgets(): array
    {
        return [
            SystemStatsOverview::class,
            RecentUsersTable::class,
        ];
    }
}
