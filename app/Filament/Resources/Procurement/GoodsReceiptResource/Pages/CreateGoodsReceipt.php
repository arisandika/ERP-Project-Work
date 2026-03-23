<?php

namespace App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;

use App\Filament\Resources\Procurement\GoodsReceiptResource;
use App\Services\Procurement\GoodsReceiptService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Simpan Header dan Item seperti biasa
        $record = static::getModel()::create($data);

        // 2. Panggil otak perhitungan Stock & Finance
        $service = app(GoodsReceiptService::class);
        $service->processAfterCreation($record);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
