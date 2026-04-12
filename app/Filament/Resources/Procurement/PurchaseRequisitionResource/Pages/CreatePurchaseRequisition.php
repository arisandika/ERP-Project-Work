<?php

namespace App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource;
use App\Models\Procurement\PurchaseRequisition;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePurchaseRequisition extends CreateRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['status'] = PurchaseRequisition::STATUS_DRAFT;
        $data['pr_number'] = PurchaseRequisition::generatePrNumber();

        return $data;
    }
}
