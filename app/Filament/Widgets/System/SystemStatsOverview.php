<?php

namespace App\Filament\Widgets\System;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;

class SystemStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('system_stats_overview', now()->addMinutes(15), function () {
            return [
                'users' => User::count(),
                'roles' => Role::count(),
                'permissions' => Permission::count(),
                'active_users' => User::where('updated_at', '>=', now()->subDays(30))->count(),
            ];
        });

        return [
            Stat::make('Total Users', number_format($stats['users']))
                ->description('Seluruh akun terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([5, 8, 12, 10, 15, 13, $stats['users']]),

            Stat::make('Active Users (30 Hari)', number_format($stats['active_users']))
                ->description('User aktif bulan ini')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([3, 5, 7, 6, 8, 9, $stats['active_users']]),

            Stat::make('Roles', number_format($stats['roles']))
                ->description('Role akses terdaftar')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make('Permissions', number_format($stats['permissions']))
                ->description('Total hak akses')
                ->descriptionIcon('heroicon-m-key')
                ->color('warning'),
        ];
    }
}
