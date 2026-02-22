<?php
namespace App\Filament\Widgets\HR;

use Filament\Widgets\Widget;

class LeaveBalancePerType extends Widget
{
    protected static string $view = 'filament.widgets.hr.leave-balance-per-type';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;
}
