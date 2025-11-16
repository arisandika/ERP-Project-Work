<?php

namespace App\Filament\Resources\Inventory\WarehouseResource\Pages;

use App\Filament\Resources\Inventory\WarehouseResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWarehouse extends ViewRecord
{
    protected static string $resource = WarehouseResource::class;

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
        return 'Lihat Gudang';
    }
}

