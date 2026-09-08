<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class ModuleAccess
{
    public static function all(): Collection
    {
        return collect(config('erp-modules', []));
    }

    public static function availableModules(): Collection
    {
        $user = Auth::user();

        if (!$user) {
            return collect();
        }

        // Cache per user per request — hindari re-query permission
        // di setiap pemanggilan ulang dalam satu request yang sama
        static $cache = [];
        $userId = $user->getKey();

        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        // Preload semua permissions sekaligus supaya ->can() tidak
        // trigger query baru per iterasi (butuh Spatie v6+)
        $user->loadMissing('roles.permissions', 'permissions');

        $result = self::all()
            ->filter(fn(array $module): bool => $user->can($module['permission']))
            ->filter(fn(array $module): bool => !(
                $module['key'] === 'attendance' && $user->hasRole('super_admin')
            ))
            ->values();

        $cache[$userId] = $result;

        return $result;
    }

    public static function current(): ?string
    {
        // Deteksi dari route name yang sedang diakses
        $routeName = request()->route()?->getName() ?? '';

        foreach (config('erp-modules', []) as $key => $module) {
            // Route HR: filament.admin.resources.hr.departments.index
            // Cek dengan .key. supaya tidak false positive
            // misal key 'hr' tidak match 'filament.admin.pages.hr-dashboard'
            if (
                str_contains($routeName, ".{$key}.") ||
                str_contains($routeName, ".{$key}-") ||
                str_ends_with($routeName, ".{$key}")
            ) {
                return $key;
            }
        }

        // Fallback ke session — untuk halaman dashboard modul
        // yang route name-nya tidak selalu mengandung key modul
        // contoh: filament.admin.pages.hr-dashboard
        return session('active_module');
    }

    public static function currentModule(): ?array
    {
        $activeModule = self::current();

        if (!$activeModule) {
            return null;
        }

        return self::all()->get($activeModule);
    }

    public static function canAccessModule(string $moduleKey): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $module = self::all()->get($moduleKey);

        if (!$module) {
            return false;
        }

        return $user->can($module['permission']);
    }

    public static function isActive(string $moduleKey): bool
    {
        return self::current() === $moduleKey;
    }

    // Masih dipakai untuk dashboard modul dan selectModule()
    public static function setActive(string $moduleKey): void
    {
        session(['active_module' => $moduleKey]);
    }

    public static function forgetActive(): void
    {
        session()->forget('active_module');
    }

    public static function defaultRedirectUrl(): string
    {
        $modules = self::availableModules();

        if ($modules->count() === 1) {
            $module = $modules->first();
            self::setActive($module['key']);

            if (isset($module['route']) && route_exists($module['route'])) {
                return route($module['route']);
            }
        }

        return route('filament.admin.pages.modules');
    }

    public static function fallbackUrlFor(string $moduleKey): ?string
    {
        $panel = Filament::getCurrentPanel();

        try {
            foreach ($panel->getNavigation() as $group) {
                foreach ($group->getItems() as $item) {
                    $url = $item->getUrl();

                    if ($url
                        && self::urlBelongsToModule($url, $moduleKey)
                        && $url !== request()->fullUrl()
                    ) {
                        return $url;
                    }
                }
            }
        } catch (\Throwable) {
            // Fall back to the registered pages and resources below.
        }

        foreach ($panel->getPages() as $page) {
            if (self::componentModule($page) !== $moduleKey) {
                continue;
            }

            try {
                if ($page::canAccess()) {
                    return $page::getUrl();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($panel->getResources() as $resource) {
            if (self::componentModule($resource) !== $moduleKey) {
                continue;
            }

            try {
                if ($resource::canViewAny()) {
                    return $resource::getUrl('index');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    protected static function urlBelongsToModule(string $url, string $moduleKey): bool
    {
        $module = self::all()->get($moduleKey);
        $routeName = $module['route'] ?? null;

        if (!$routeName || !route_exists($routeName)) {
            return false;
        }

        $modulePath = trim((string) parse_url(route($routeName), PHP_URL_PATH), '/');
        $modulePrefix = str($modulePath)->beforeLast('/')->trim('/')->toString();
        $candidatePath = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $modulePrefix !== ''
            && ($candidatePath === $modulePrefix || str_starts_with($candidatePath, "{$modulePrefix}/"));
    }

    protected static function componentModule(string $component): ?string
    {
        if (!property_exists($component, 'module')) {
            return null;
        }

        return (new \ReflectionClass($component))->getStaticPropertyValue('module');
    }
}

if (!function_exists('route_exists')) {
    function route_exists(string $name): bool
    {
        return app('router')->has($name);
    }
}
