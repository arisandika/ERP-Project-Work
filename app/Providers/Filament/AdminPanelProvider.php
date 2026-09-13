<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;

use App\Http\Middleware\RecoverFromLivewireRedirectorBug;
use App\Livewire\Breezy\PersonalInfo;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Filament\Navigation\MenuItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $this->registerPwaHooks();

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
            ->font('Plus Jakarta Sans')
            ->brandName('Nexicon ERP Dashboard')
            ->brandLogo(fn() => view('filament.admin.logo'))
            ->favicon(url('favicon.ico'))
            ->defaultThemeMode(ThemeMode::Dark)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                RecoverFromLivewireRedirectorBug::class,
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
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                    ])
            ])
            ->sidebarCollapsibleOnDesktop(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('750ms')
            ->userMenuItems([
                'switch_module' => MenuItem::make()
                    ->label('Ganti Modul')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(fn (): string => route('filament.admin.pages.modules')),
            ])
            ;
    }

    /**
     * Registrasi PWA (manifest + service worker + tombol install) untuk panel admin.
     */
    private function registerPwaHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            function (): string {
                return implode('', [
                    '<link rel="manifest" href="' . asset('manifest.json') . '">',
                    '<link rel="apple-touch-icon" href="' . asset('icons/apple-touch-icon.png') . '">',
                    '<meta name="theme-color" content="#1c9cf0">',
                    '<meta name="mobile-web-app-capable" content="yes">',
                    '<meta name="apple-mobile-web-app-capable" content="yes">',
                    '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">',
                    '<meta name="apple-mobile-web-app-title" content="Nexicon ERP">',
                ]);
            }
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function (): string {
                $icon = asset('icons/apple-touch-icon.png');
                $sw = asset('sw.js');

                $html = <<<'HTML'
                <style>#pwa-banner[hidden]{display:none !important}</style>
                <div id="pwa-banner" hidden class="fixed top-0 inset-x-0 z-50 flex items-center gap-3 px-4 py-3 text-sm bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md">
                    <img src="%ICON%" alt="" class="w-9 h-9 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white">Instal Nexicon ERP</div>
                        <div class="text-gray-500 dark:text-gray-400 truncate">Pasang di layar utama HP untuk akses cepat</div>
                    </div>
                    <button type="button" id="pwa-install" class="px-3 py-1.5 rounded-lg text-white bg-blue-600 hover:bg-blue-500">Pasang</button>
                    <button type="button" id="pwa-dismiss" class="p-1 text-gray-400 hover:text-gray-600" aria-label="Tutup">✕</button>
                </div>
                <script>
                    const pwaBanner = document.getElementById('pwa-banner');
                    const showPwaBanner = () => { if (pwaBanner) pwaBanner.hidden = false; };
                    const hidePwaBanner = () => { if (pwaBanner) pwaBanner.hidden = true; };
                    const pwaInstall = document.getElementById('pwa-install');
                    const pwaDismiss = document.getElementById('pwa-dismiss');

                    // Android / Chrome desktop: instal melalui tombol
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        window.deferredPrompt = e;
                        showPwaBanner();
                    });

                    // iOS: tidak ada beforeinstallprompt → arahkan ke "Tambah ke Layar Utama"
                    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
                    if (isIos && !window.navigator.standalone) showPwaBanner();

                    pwaInstall?.addEventListener('click', async () => {
                        if (isIos) {
                            // iOS: tidak pintasan install → tampilkan instruksi
                            const div = document.createElement('div');
                            div.style.cssText = 'position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.5);display:flex;align-items:flex-end;justify-content:center;padding:20px';
                            div.innerHTML = '<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl p-5 max-w-sm w-full shadow-xl">' +
                                '<div class="font-semibold text-base mb-1">Pasang Nexicon ERP</div>' +
                                '<ol class="list-decimal pl-5 text-sm space-y-1 mb-4">' +
                                '<li>Tekan ikon <b>Bagikan</b> di Safari</li>' +
                                '<li>Pilih <b>Tambah ke Layar Utama</b></li>' +
                                '<li>Tekan <b>Tambah</b></li></ol>' +
                                '<button type="button" class="w-full px-3 py-2 rounded-lg text-white bg-blue-600">OK, Mengerti</button></div>';
                            document.body.appendChild(div);
                            div.querySelector('button').addEventListener('click', () => div.remove());
                            return;
                        }
                        const prompt = window.deferredPrompt;
                        if (prompt) { await prompt.prompt(); window.deferredPrompt = null; }
                        hidePwaBanner();
                    });
                    pwaDismiss?.addEventListener('click', hidePwaBanner);

                    if ('serviceWorker' in navigator) {
                        window.addEventListener('load', () => navigator.serviceWorker.register('%SW%', { scope: '/' }));
                    }
                </script>
                HTML;

                return str_replace(['%ICON%', '%SW%'], [$icon, $sw], $html);
            }
        );
    }
}
