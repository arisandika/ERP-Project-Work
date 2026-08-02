<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Inventory\ProductResource;
use App\Filament\Widgets\Inventory\CategoryValuationChart;
use App\Filament\Widgets\Inventory\InventoryStatsOverview;
use App\Filament\Widgets\Inventory\MovementAnalysisChart;
use App\Filament\Widgets\Inventory\StockWarehouseChart;
use App\Filament\Resources\Inventory\InventoryMonitoringResource;
use Filament\Pages\Dashboard;
use Filament\Actions\Action;

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

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('createProduct')
            //     ->label('Produk Baru')
            //     ->icon('heroicon-m-plus')
            //     ->url(ProductResource::getUrl('create')),

            // Action::make('stockReport')
            //     ->label('Laporan Stok')
            //     ->icon('heroicon-m-document-text')
            //     ->color('gray')
            //     ->url('/admin/inventory/stock-reports'),

            Action::make('liveMonitor')
                ->label('Live Monitor')
                ->icon('heroicon-m-chart-bar')
                ->color('gray')
                ->url(InventoryMonitoringResource::getUrl('index')),
        ];
    }

    public function getWidgets(): array
    {
        return [
            InventoryStatsOverview::class,
            MovementAnalysisChart::class,
            CategoryValuationChart::class,
            StockWarehouseChart::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
