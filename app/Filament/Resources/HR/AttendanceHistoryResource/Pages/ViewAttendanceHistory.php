<?php

namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceHistory extends ViewRecord
{
    protected static string $resource = AttendanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->url(static::getResource()::getUrl())->button()->color('gray'),
        ];
    }
}
