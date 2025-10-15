<?php

namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Resources\HR\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
