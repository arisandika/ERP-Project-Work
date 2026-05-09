<?php

namespace App\Filament\Pages\Project;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardProject extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'project';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.project.dashboard-project';
}
