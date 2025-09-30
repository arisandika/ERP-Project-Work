<?php

namespace App\Filament\Resources\HR\OfficeResource\Pages;

use App\Filament\Resources\HR\OfficeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOffice extends ViewRecord
{
    protected static string $resource = OfficeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
