<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Sections;

use Filament\Widgets\Widget;

class AnalyticsSectionHeaderWidget extends Widget
{
    protected static string $view = 'filament.widgets.employee-analytics.sections.analytics-section-header-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public string $title = '';

    public string $description = '';

    public string $icon = 'heroicon-o-chart-bar';
}
