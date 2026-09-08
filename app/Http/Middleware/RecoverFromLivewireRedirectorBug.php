<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;

class RecoverFromLivewireRedirectorBug
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'SupportRedirects\Redirector::$headers')) {
                $fallbackUrl = null;

                try {
                    $moduleKey = ModuleAccess::current();
                    $fallbackUrl = $moduleKey
                        ? ModuleAccess::fallbackUrlFor($moduleKey)
                        : null;
                } catch (\Throwable) {
                    // Use the module selector if resolving a module fallback fails.
                }

                return response()->redirectTo(
                    $fallbackUrl ?? route('filament.admin.pages.modules')
                );
            }

            throw $e;
        }
    }
}