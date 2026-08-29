<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use Filament\Widgets\ChartWidget;

class ProjectProgressChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Progress per Project';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 3,
    ];

    protected function getData(): array
    {
        $projects = Project::query()
            ->orderByDesc('pinned_date')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Progress (%)',
                    'data'            => $projects->map(fn($p) => $p->progress_percentage)->toArray(),
                    'backgroundColor' => $projects->map(
                        fn($p) => $p->progress_percentage >= 100 ? '#22C55E'
                            : ($p->progress_percentage >= 50 ? '#3B82F6' : '#F59E0B')
                    )->toArray(),
                ],
            ],
            'labels'   => $projects->map(fn($p) => $p->name)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins'   => [
                'legend' => ['display' => false],
            ],
            'scales'    => [
                'x' => ['beginAtZero' => true, 'max' => 100],
            ],
        ];
    }
}
