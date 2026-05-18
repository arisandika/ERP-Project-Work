<?php

namespace App\Filament\Pages\CRM;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardCRM extends Page
{
    // 1. Panggil Trait
    use BelongsToModule;

    // 2. Definisikan ke modul mana halaman ini milik (harus sama dengan format Spatie)
    protected static ?string $module = 'crm';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static string $view = 'filament.pages.crm.dashboard-crm';

    protected static ?string $slug = 'crm/dashboard';

    protected static ?string $routePath = 'crm/dashboard';

    protected static ?string $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard CRM';

    protected static ?string $title = 'Dashboard CRM';



}
