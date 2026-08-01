<?php
namespace App\Filament\Pages\CRM;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\CRM\CrmOverviewWidget;
use App\Filament\Widgets\CRM\DealByStageChartWidget;
use App\Filament\Widgets\CRM\ExpiringQuotationsTableWidget;
use App\Filament\Widgets\CRM\HotDealsTableWidget;
use App\Filament\Widgets\CRM\LeadSourceChartWidget;
use App\Filament\Widgets\CRM\SalesFunnelChartWidget;
use App\Filament\Widgets\CRM\SalesPerformanceTableWidget;
use App\Filament\Widgets\CRM\StaleLeadsTableWidget;
use App\Filament\Widgets\CRM\WonLostTrendChartWidget;
use Filament\Pages\Page;

class DashboardCRM extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'crm';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.crm.dashboard-crm';

    protected static ?string $slug = 'crm/dashboard';

    protected static string $routePath = 'crm/dashboard';

    protected static ?string $navigationLabel = 'Dashboard CRM';

    protected static ?string $title = 'Dashboard CRM';

    protected function getHeaderWidgets(): array
    {
        return [
            CrmOverviewWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            SalesFunnelChartWidget::class,
            LeadSourceChartWidget::class,
            DealByStageChartWidget::class,
            WonLostTrendChartWidget::class,
            SalesPerformanceTableWidget::class,
            HotDealsTableWidget::class,
            ExpiringQuotationsTableWidget::class,
            StaleLeadsTableWidget::class,
        ];
    }

    protected function getColumns(): int | array
    {
        return 4;
    }
}
