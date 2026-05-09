<?php

namespace App\Filament\Pages\HR;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardHR extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'hr';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.hr.dashboard-hr';
}
