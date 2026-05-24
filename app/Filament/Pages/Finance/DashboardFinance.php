<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Finance\LatestUnpaidInvoices;
use App\Filament\Widgets\Finance\LatestUnpaidPurchaseOrders;
use App\Filament\Widgets\Finance\ReimburseApprovalOverview;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class DashboardFinance extends Page
{
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Finance Dashboard';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.finance.dashboard-finance';

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReimburseApprovalOverview::class,
            LatestUnpaidInvoices::class,
            LatestUnpaidPurchaseOrders::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
