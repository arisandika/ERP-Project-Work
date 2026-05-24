<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Terima Barang'),
        ];
    }
}
