<?php

namespace App\Filament\Resources\Finance\FinancialRecordResource\Pages;

use App\Filament\Resources\Finance\FinancialRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialRecord extends CreateRecord
{
    protected static string $resource = FinancialRecordResource::class;

    public function getTitle(): string
    {
        return 'Tambah Transaksi';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] =
            auth()->user()?->employee?->id;

        return $data;
    }
}
