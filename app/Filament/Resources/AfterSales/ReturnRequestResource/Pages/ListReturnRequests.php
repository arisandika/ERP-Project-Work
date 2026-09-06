<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    /**
     * ReturnRequest TIDAK dibuat dari panel admin — dibuat customer lewat
     * Customer Portal (ReturnRequestResource::canCreate() = false).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
