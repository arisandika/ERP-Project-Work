<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardSystem extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'system';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.dashboard-system';
}
