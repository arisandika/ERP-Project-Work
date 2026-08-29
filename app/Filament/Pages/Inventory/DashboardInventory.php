<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Widgets\Inventory\InventoryStatsOverview;
use App\Filament\Widgets\Inventory\MovementAnalysisChart;
use App\Filament\Widgets\Inventory\StockWarehouseChart;
use Filament\Pages\Dashboard;

class DashboardInventory extends Dashboard
{
    use BelongsToModule;

    protected static ?string $module = 'inventory';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.inventory.dashboard-inventory';

    protected static ?string $slug = 'inventory/dashboard';

    protected static string $routePath = 'inventory/dashboard';

    protected static ?string $navigationLabel = 'Dashboard Inventory';

    protected static ?string $title = 'Dashboard Inventory';

    protected static ?int $navigationSort = 1;

    /**
     * Dashboard widgets — keep minimal:
     * 1. KPI cards (top row)
     * 2. Stock movement trend (line)
     * 3. Stock distribution by warehouse (doughnut)
     */
    public function getWidgets(): array
    {
        return [
            InventoryStatsOverview::class,
            MovementAnalysisChart::class,
            StockWarehouseChart::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }
}
