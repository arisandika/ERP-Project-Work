<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ModuleAccess
{
    public static function all(): Collection
    {
        return collect(config('erp-modules', []));
    }

    public static function availableModules(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return self::all()
            ->filter(fn (array $module): bool => $user->can($module['permission']))
            ->values();
    }

    public static function current(): ?string
    {
        return session('active_module');
    }

    public static function currentModule(): ?array
    {
        $activeModule = self::current();

        if (! $activeModule) {
            return null;
        }

        return self::all()->get($activeModule);
    }

    public static function canAccessModule(string $moduleKey): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $module = self::all()->get($moduleKey);

        if (! $module) {
            return false;
        }

        return $user->can($module['permission']);
    }

    public static function isActive(string $moduleKey): bool
    {
        return self::current() === $moduleKey;
    }

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
}

if (! function_exists('route_exists')) {
    function route_exists(string $name): bool
    {
        return app('router')->has($name);
    }
}
