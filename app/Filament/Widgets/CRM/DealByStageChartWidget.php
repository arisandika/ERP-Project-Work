<?php
namespace App\Filament\Widgets\CRM;

use App\Models\CRM\DealStage;
use Filament\Widgets\ChartWidget;

class DealByStageChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pipeline Value per Stage';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 2,
    ];

    protected function getData(): array
    {
        $stages = DealStage::withSum(['deals' => function ($query) {
            $query->where('status', 'open');
        }], 'estimated_value')
            ->withCount(['deals' => function ($query) {
                $query->where('status', 'open');
            }])
            ->orderBy('sort_order')
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Total Value (IDR)',
                    'data'            => $stages->pluck('deals_sum_estimated_value')->map(fn($v) => $v ?? 0)->toArray(),
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels'   => $stages->map(fn($s) => $s->name . ' (' . $s->deals_count . ')')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
