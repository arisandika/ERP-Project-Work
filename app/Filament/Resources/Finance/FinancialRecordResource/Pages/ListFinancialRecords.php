<?php

namespace App\Filament\Resources\Finance\FinancialRecordResource\Pages;

use App\Filament\Resources\Finance\FinancialRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialRecords extends ListRecords
{
    protected static string $resource = FinancialRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Transaksi'),
        ];
    }
}
