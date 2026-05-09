<?php

namespace App\Filament\Pages\Procurement;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardProcurement extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'procurement';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.procurement.dashboard-procurement';
}
