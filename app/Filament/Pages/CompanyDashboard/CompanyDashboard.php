<?php
namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class CompanyDashboard extends Page
{
    use HasPageShield;
    
    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }

}
