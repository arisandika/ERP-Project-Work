<?php
namespace App\Filament\Resources\HR\OvertimeMonitoringResource\Pages;

use App\Filament\Resources\HR\OvertimeMonitoringResource;
use App\Filament\Widgets\HR\OvertimeByDepartmentChart;
use App\Filament\Widgets\HR\OvertimeOverviewWidget;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeMonitoring extends ListRecords
{
    protected static string $resource = OvertimeMonitoringResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            OvertimeOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            OvertimeByDepartmentChart::class,
        ];
    }
}
