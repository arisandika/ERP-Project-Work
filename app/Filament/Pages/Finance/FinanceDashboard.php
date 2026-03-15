<?php

namespace App\Filament\Pages\Finance;

use Filament\Pages\Page;
use App\Filament\Resources\Finance\FinancialRecordResource\Widgets\FinanceOverview;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dashboard Keuangan';

    protected static string $view = 'filament.pages.finance.finance-dashboard';

    // Panggil Widget kotak-kotak tadi ke sini
    protected function getHeaderWidgets(): array
    {
        return [
            FinanceOverview::class,
        ];
    }
}
