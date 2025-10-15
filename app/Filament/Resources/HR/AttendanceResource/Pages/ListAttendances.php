<?php

namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\HR\AttendanceResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(AttendanceExporter::class)
                ->label('Export Presensi')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
        ];
    }
}
