<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use Filament\Widgets\ChartWidget;

class ProjectProgressChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Progress per Project';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $projects = Project::whereDate('end_date', '>=', now()->subDays(90))
            ->orWhereNull('end_date')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Progress (%)',
                    'data'            => $projects->pluck('progress_percentage')->toArray(),
                    'backgroundColor' => $projects->map(fn($p) => $p->color ?? '#6B7280')->toArray(),
                ],
            ],
            'labels'   => $projects->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ];
    }
}
