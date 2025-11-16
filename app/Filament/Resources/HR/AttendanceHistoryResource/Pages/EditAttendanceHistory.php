<?php

namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceHistory extends EditRecord
{
    protected static string $resource = AttendanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
