<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Office;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class AttendanceMapOverview extends Widget
{
    
    
    protected static string $view = 'filament.widgets.hr.attendance-map-overview';

    protected int|string|array $columnSpan = 'full';

    protected int|string|array $height = '600px';

    protected static bool $isLazy = false;
}
