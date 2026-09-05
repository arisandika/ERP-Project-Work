<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use App\Models\AfterSales\ReturnRequest;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnRequest extends CreateRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Dibuat manual oleh CS berarti barang sudah diterima fisik dari klien.
        $data['status'] = ReturnRequest::STATUS_RECEIVED;
        $data['received_date'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
