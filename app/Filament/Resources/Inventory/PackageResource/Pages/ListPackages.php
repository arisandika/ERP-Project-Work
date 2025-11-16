<?php

namespace App\Filament\Resources\Inventory\PackageResource\Pages;

use App\Filament\Resources\Inventory\PackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackages extends ListRecords
{
    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Paket Layanan'),
        ];
    }
}
