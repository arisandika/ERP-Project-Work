<?php

namespace App\Filament\Resources\Inventory\InventoryMonitoringResource\Pages;

use App\Filament\Resources\Inventory\InventoryMonitoringResource;
use App\Filament\Widgets\Inventory\InventorySummaryOverview;
use App\Filament\Widgets\Inventory\StockMovementChart;
use App\Filament\Widgets\LowStockAlert;
use App\Filament\Widgets\LowStockStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListInventoryMonitorings extends ListRecords
{
    protected static string $resource = InventoryMonitoringResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InventorySummaryOverview::class,
            LowStockStatsOverview::class,
            LowStockAlert::class,
        ];
    }
}


