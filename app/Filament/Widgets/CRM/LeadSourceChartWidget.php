<?php
namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Lead;
use Filament\Widgets\ChartWidget;

class LeadSourceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Sumber Lead';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $sources = Lead::selectRaw('source, count(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source');

        $labelMap = [
            'manual'       => 'Manual',
            'website'      => 'Website/Form',
            'social_media' => 'Social Media',
            'referral'     => 'Referral',
            'cold_call'    => 'Cold Call',
            'ads'          => 'Iklan Berbayar',
            'other'        => 'Lainnya',
        ];

        return [
            'datasets' => [
                [
                    'data'            => $sources->values()->toArray(),
                    'backgroundColor' => ['#6366f1', '#22c55e', '#f59e0b', '#3b82f6', '#ec4899', '#ef4444', '#9ca3af'],
                ],
            ],
            'labels'   => $sources->keys()->map(fn($k) => $labelMap[$k] ?? ucfirst($k))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
