<?php

namespace App\Filament\Resources\Inventory\SerialNumberResource\Pages;

use App\Filament\Resources\Inventory\SerialNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSerialNumber extends ViewRecord
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
