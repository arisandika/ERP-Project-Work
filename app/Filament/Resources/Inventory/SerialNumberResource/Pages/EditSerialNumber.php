<?php

namespace App\Filament\Resources\Inventory\SerialNumberResource\Pages;

use App\Filament\Resources\Inventory\SerialNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSerialNumber extends EditRecord
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
