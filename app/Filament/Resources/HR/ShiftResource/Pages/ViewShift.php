<?php

namespace App\Filament\Resources\HR\ShiftResource\Pages;

use App\Filament\Resources\HR\ShiftResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewShift extends ViewRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->url(static::getResource()::getUrl()) 
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Jam Kerja';
    }
}
