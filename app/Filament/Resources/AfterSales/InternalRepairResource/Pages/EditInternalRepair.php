<?php

namespace App\Filament\Resources\AfterSales\InternalRepairResource\Pages;

use App\Filament\Resources\AfterSales\InternalRepairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInternalRepair extends EditRecord
{
    protected static string $resource = InternalRepairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
