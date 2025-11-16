<?php

namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceHistories extends ListRecords
{
    protected static string $resource = AttendanceHistoryResource::class;
}
