<?php
namespace App\Filament\Widgets\Marketing;

use App\Models\Marketing\PopupBanner;
use App\Models\Marketing\PromoCode;
use App\Models\Marketing\Slider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalSliders = Slider::count();
        $activeSliders = Slider::active()->count();

        $totalBanners = PopupBanner::count();
        $activeBanners = PopupBanner::activeNow()->count();

        $totalPromos = PromoCode::count();
        $activePromos = PromoCode::available()->count();

        $totalUsage = PromoCode::sum('times_used');

        // Generate wavy chart data — 7 points trending toward current value
        $wavy = fn (int $end, int $max = 0): array => [
            (int) ($end * 0.3),
            (int) ($end * 0.5),
            (int) ($end * 0.4),
            (int) ($end * 0.7),
            (int) ($end * 0.6),
            (int) ($end * 0.85),
            $end,
        ];

        return [
            Stat::make('Total Slider', $totalSliders)
                ->description("{$activeSliders} aktif / " . ($totalSliders - $activeSliders) . " nonaktif")
                ->descriptionIcon('heroicon-m-photo')
                ->color('info')
                ->chart($wavy($totalSliders)),

            Stat::make('Total Banner', $totalBanners)
                ->description("{$activeBanners} aktif / " . ($totalBanners - $activeBanners) . " nonaktif")
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('success')
                ->chart($wavy($totalBanners)),

            Stat::make('Promo Code', $totalPromos)
                ->description("{$activePromos} tersedia dari {$totalPromos} total")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary')
                ->chart($wavy($totalPromos)),

            Stat::make('Total Penggunaan Promo', number_format($totalUsage))
                ->description('Seluruh kode promo terpakai')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart($wavy($totalUsage)),
        ];
    }
}
