<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;

use App\Filament\Resources\Procurement\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function getTitle(): string
    {
        return 'Buat Purchase Order';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'draft';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
