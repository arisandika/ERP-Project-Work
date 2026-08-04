<?php

namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Customer;
use App\Models\CRM\Deal;
use App\Models\CRM\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CRMStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getStats(): array
    {
        $stats = Cache::remember('crm_stats_overview', now()->addMinutes(15), function () {
            return [
                'total_leads' => Lead::count(),
                'new_leads' => Lead::where('status', 'new')->count(),
                'total_deals' => Deal::count(),
                'open_deals' => Deal::where('status', 'open')->count(),
                'won_deals' => Deal::where('status', 'won')->count(),
                'total_customers' => Customer::count(),
                'pipeline_value' => Deal::where('status', 'open')->sum('estimated_value'),
                'won_value' => Deal::where('status', 'won')->sum('estimated_value'),
            ];
        });

        return [
            Stat::make('Total Leads', number_format($stats['total_leads']))
                ->description("{$stats['new_leads']} baru belum dihubungi")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([8, 12, 10, 15, 13, 18, $stats['total_leads']]),

            Stat::make('Active Deals', number_format($stats['open_deals']))
                ->description("{$stats['total_deals']} total deal")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->chart([3, 5, 4, 7, 6, 8, $stats['open_deals']]),

            Stat::make('Pipeline Value', 'Rp ' . number_format($stats['pipeline_value'], 0, ',', '.'))
                ->description('Estimasi deal aktif')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Deals Won', 'Rp ' . number_format($stats['won_value'], 0, ',', '.'))
                ->description("{$stats['won_deals']} deal berhasil")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Total Customers', number_format($stats['total_customers']))
                ->description('Pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }
}
