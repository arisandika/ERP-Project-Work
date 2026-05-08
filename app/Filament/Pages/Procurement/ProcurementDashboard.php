<?php

namespace App\Filament\Pages\Procurement;

use App\Filament\Concerns\BelongsToModule;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;

class ProcurementDashboard extends BaseDashboard
{
    /**
     * Resolusi Konflik Trait
     * Kita harus menggabungkan logika Shield (Permission) dan Modul (Tenant Access).
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    // Identitas Modul untuk pengecekan BelongsToModule
    protected static ?string $module = 'procurement';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 0;

    protected static string $routePath = 'procurement-dashboard';
    protected static ?string $title = 'Dashboard Procurement';

    /**
     * Override akses: Halaman hanya bisa diakses jika:
     * 1. User memiliki permission/role (Shield)
     * 2. Modul Procurement aktif (BelongsToModule)
     */
    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    /**
     * Pastikan navigasi di sidebar juga mengikuti aturan yang sama
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    /**
     * Pendaftaran Widget
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Procurement\ProcurementStatsOverview::class,
            \App\Filament\Widgets\Procurement\ProcurementPoStatusChart::class,
            \App\Filament\Widgets\Procurement\ProcurementMonthlyCostChart::class,
            \App\Filament\Widgets\Procurement\ProcurementLatestPoTable::class,
        ];
    }
}
