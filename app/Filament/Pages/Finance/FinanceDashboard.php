<?php

namespace App\Filament\Pages\Finance;

use Filament\Pages\Page;
use App\Filament\Resources\Finance\FinancialRecordResource\Widgets\FinanceOverview;
use App\Filament\Widgets\Finance\LatestUnpaidInvoices;
use App\Filament\Widgets\Finance\LatestUnpaidPurchaseOrders;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Dashboard Keuangan';
    protected static string $view = 'filament.pages.finance.finance-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceOverview::class,             // Kotak Angka Total
            LatestUnpaidInvoices::class,        // Radar Piutang Klien (Merah)
            LatestUnpaidPurchaseOrders::class,  // Radar Hutang Supplier (Kuning)
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
