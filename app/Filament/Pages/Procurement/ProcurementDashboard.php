<?php

namespace App\Filament\Pages\Procurement;

use Filament\Pages\Dashboard as BaseDashboard;

class ProcurementDashboard extends BaseDashboard
{
    // Mengatur posisi di menu sidebar agar kumpul sama modul Procurement
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 0; // Paling atas

    // Konfigurasi URL dan Judul
    protected static string $routePath = 'procurement-dashboard';
    protected static ?string $title = 'Dashboard Procurement';

    // Daftarkan 3 Widget yang udah lu bikin sebelumnya ke sini
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
