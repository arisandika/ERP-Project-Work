<?php

namespace App\Filament\Concerns;

use App\Support\ModuleAccess;

trait BelongsToModule
{
    public static function shouldRegisterNavigation(): bool
    {
        if (!static::canAccessCurrentModule()) {
            return false;
        }

        // Cek permission view_any untuk Resource
        if (method_exists(static::class, 'getModel')) {
            return static::canViewAny();
        }

        return true;
    }

    public static function canAccess(): bool
    {
        return static::canAccessCurrentModule();
    }

    public static function canViewAny(): bool
    {
        if (!static::canAccessCurrentModule()) {
            return false;
        }

        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Kalau ini Resource, cek via policy (yang Shield generate)
        if (method_exists(static::class, 'getModel')) {
            return $user->can('viewAny', static::getModel());
        }

        return true;
    }

    protected static function canAccessCurrentModule(): bool
    {
        $moduleKey = static::$module ?? null;

        if (!$moduleKey) {
            return false;
        }

        return ModuleAccess::isActive($moduleKey)
            && ModuleAccess::canAccessModule($moduleKey);
    }
}