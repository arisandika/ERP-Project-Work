<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Concerns\BelongsToModule;
use Filament\Pages\Page;

class DashboardInventory extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'inventory';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.inventory.dashboard-inventory';
}
