<?php
namespace App\Filament\Widgets\HR;

use Filament\Widgets\Widget;

class AttendanceMapOverview extends Widget
{
    protected static string $view = 'filament.widgets.hr.attendance-map-overview';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 2,
    ];

    protected int|string|array $height = '600px';

    protected static bool $isLazy = false;
}
