<?php

namespace App\Filament\Resources\Inventory\UnitResource\Pages;

use App\Filament\Resources\Inventory\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Satuan';
    }
}
