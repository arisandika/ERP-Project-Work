<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Filament\Widgets\HR\LeaveBalancePerType;
use App\Filament\Widgets\HR\LeaveOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajukan Cuti'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveOverview::class,
            LeaveBalancePerType::class,
        ];
    }
}
