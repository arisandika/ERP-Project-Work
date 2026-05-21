<?php

namespace App\Filament\Resources\AfterSales\InternalRepairResource\Pages;

use App\Filament\Resources\AfterSales\InternalRepairResource;
use Filament\Resources\Pages\ListRecords;

class ListInternalRepairs extends ListRecords
{
    protected static string $resource = InternalRepairResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
