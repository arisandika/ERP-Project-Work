<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\CompanyDashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login(Login::class)
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'success' => Color::Emerald,
                'yellow' => Color::Yellow,
                'warning' => Color::Orange,
                'secondary' => Color::Purple,
                'indigo' => Color::Indigo,
                'green' => Color::Green,
                'primary' => Color::hex('#1c9cf0'),
                'info' => Color::hex('#1c9cf0'),
                'light' => Color::hex('#ffffff'),
                'dark' => Color::hex('#1c2433'),
            ])
            ->font('Poppins')
            ->brandName('Nexicon ERP Dashboard')
            ->brandLogo(fn() => view('filament.admin.logo'))
            ->favicon(url('favicon.ico'))
            ->defaultThemeMode(ThemeMode::Dark)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                CompanyDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->theme(asset('css/filament/admin/theme.css'))
            ->plugins([
                FilamentApexChartsPlugin::make(),
                FilamentShieldPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, 
                        userMenuLabel: 'My Profile', 
                        shouldRegisterNavigation: false, 
                        navigationGroup: 'Settings', 
                        hasAvatars: false, 
                        slug: 'my-profile' 
                    )
            ])
            ->sidebarCollapsibleOnDesktop(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('750ms')
            ;
    }
}
