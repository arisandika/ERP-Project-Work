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
                ->url(function () {
                    // Get filter values from current request or table state
                    $fromDate = request()->input('tableFilters.transaction_date.from');
                    $untilDate = request()->input('tableFilters.transaction_date.until');
                    
                    // Build URL with query parameters
                    $url = route('inventory.transaction-report.download-pdf');
                    $params = [];
                    
                    if ($fromDate) {
                        $params['from_date'] = $fromDate;
                    }
                    
                    if ($untilDate) {
                        $params['until_date'] = $untilDate;
                    }
                    
                    // Add current table filters to URL if available
                    $tableFilters = request()->get('tableFilters', []);
                    if (isset($tableFilters['transaction_date']) && is_array($tableFilters['transaction_date'])) {
                        if (isset($tableFilters['transaction_date']['from'])) {
                            $params['from_date'] = $tableFilters['transaction_date']['from'];
                        }
                        if (isset($tableFilters['transaction_date']['until'])) {
                            $params['until_date'] = $tableFilters['transaction_date']['until'];
                        }
                    }
                    
                    if (!empty($params)) {
                        $url .= '?' . http_build_query($params);
                    }
                    
                    return $url;
                })
                ->openUrlInNewTab(),
        ];
    }
}

