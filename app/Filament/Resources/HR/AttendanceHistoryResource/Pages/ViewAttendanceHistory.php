<?php

namespace App\Filament\Resources\HR\AttendanceHistoryResource\Pages;

use App\Filament\Resources\HR\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceHistory extends ViewRecord
{
    protected static string $resource = AttendanceHistoryResource::class;

    public function mount(int|string $record): void
    {
        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses melihat riwayat presensi.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->url(static::getResource()::getUrl())->button()->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Riwayat Presensi';
    }
}
