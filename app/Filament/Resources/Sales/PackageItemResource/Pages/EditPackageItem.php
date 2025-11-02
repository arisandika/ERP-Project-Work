<?php

namespace App\Filament\Resources\Sales\PackageItemResource\Pages;

use App\Filament\Resources\Sales\PackageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackageItem extends EditRecord
{
    protected static string $resource = PackageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

