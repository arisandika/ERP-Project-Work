<?php
namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Deal;
use Filament\Widgets\ChartWidget;

class WonLostTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Won vs Lost (6 Bulan Terakhir)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $labels   = [];
        $wonData  = [];
        $lostData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $wonData[] = Deal::where('status', Deal::STATUS_CLOSED_WON)
                ->whereMonth('close_date', $month->month)
                ->whereYear('close_date', $month->year)
                ->count();

            $lostData[] = Deal::where('status', Deal::STATUS_CLOSED_LOST)
                ->whereMonth('close_date', $month->month)
                ->whereYear('close_date', $month->year)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Won',
                    'data'            => $wonData,
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill'            => true,
                ],
                [
                    'label'           => 'Lost',
                    'data'            => $lostData,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill'            => true,
                ],
            ],
            'labels'   => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
