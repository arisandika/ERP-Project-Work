<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnRequest extends CreateRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
