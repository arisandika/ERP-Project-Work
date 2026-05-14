<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Mitra Supplier', Supplier::count())
                ->description('Database vendor terdaftar')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Vendor Aktif', Supplier::where('status', 'active')->count())
                ->description('Siap transaksi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Vendor Terblokir', Supplier::where('status', 'blacklisted')->count())
                ->description('Dalam pengawasan')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('danger'),
        ];
    }
}
