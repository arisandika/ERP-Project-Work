<?php

namespace App\Filament\Resources\Finance\FinancialRecordResource\Pages;

use App\Filament\Resources\Finance\FinancialRecordResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialRecord extends ViewRecord
{
    protected static string $resource = FinancialRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            Action::make('Kembali')
                ->url(static::getResource()::getUrl())
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Transaksi';
    }
}
