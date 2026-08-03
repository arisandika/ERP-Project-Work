<?php

namespace App\Filament\Resources\Procurement\SupplierResource\Pages;

use App\Filament\Resources\Procurement\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Filter out empty contact entries
        if (!empty($data['contacts'])) {
            $data['contacts'] = array_values(array_filter($data['contacts'], fn ($c) => !empty($c['name']) || !empty($c['phone']) || !empty($c['email'])));
        }

        return $data;
    }
}
