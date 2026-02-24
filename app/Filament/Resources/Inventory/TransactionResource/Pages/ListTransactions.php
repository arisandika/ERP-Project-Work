<?php

namespace App\Filament\Resources\Inventory\TransactionResource\Pages;

use App\Filament\Resources\Inventory\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Stock Product'),
        ];
    }
}

