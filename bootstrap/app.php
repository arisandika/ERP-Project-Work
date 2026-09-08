<?php

use App\Http\Middleware\EnsureCustomerPortalAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Support\ModuleAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'customer.portal' => EnsureCustomerPortalAuthenticated::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\RecoverFromLivewireRedirectorBug::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 403 || $request->expectsJson() || $request->header('X-Livewire')) {
                return null;
            }

            $moduleKey = ModuleAccess::current();
            $fallbackUrl = $moduleKey ? ModuleAccess::fallbackUrlFor($moduleKey) : null;

            if ($fallbackUrl && $fallbackUrl !== $request->fullUrl()) {
                return response()->redirectTo($fallbackUrl);
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'message' => 'Session expired, please refresh.',
                ], 419);
            }
            return response()
                ->redirectTo(route('filament.admin.auth.login'))
                ->with('error', 'Session expired, please refresh.');
        });
    })->create();
