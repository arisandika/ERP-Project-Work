<?php

namespace App\Filament\Concerns;

use App\Support\ModuleAccess;

trait BelongsToModule
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessCurrentModule();
    }

    public static function canAccess(): bool
    {
        return static::canAccessCurrentModule();
    }

    public static function canViewAny(): bool
    {
        return static::canAccessCurrentModule();
    }

    protected static function canAccessCurrentModule(): bool
    {
        $moduleKey = static::$module ?? null;

        if (! $moduleKey) {
            return false;
        }

        return ModuleAccess::isActive($moduleKey)
            && ModuleAccess::canAccessModule($moduleKey);
    }
}
