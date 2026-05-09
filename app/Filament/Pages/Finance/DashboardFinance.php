<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardFinance extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'finance';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.finance.dashboard-finance';
}
