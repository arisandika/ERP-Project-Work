<?php

namespace App\Filament\Resources\Inventory\TransactionReportResource\Pages;

use App\Filament\Resources\Inventory\TransactionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactionReports extends ListRecords
{
    protected static string $resource = TransactionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Unduh PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(route('inventory.transaction-report.download-pdf'))
                ->openUrlInNewTab()
                ->tooltip('Unduh laporan transaksi product dalam format PDF'),
        ];
    }
}

