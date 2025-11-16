<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user     = auth()->user();
        $employee = $user?->employee;

        if (! $employee) {
            return [];
        }

        $quota = Leave::sum('days_count');
        $used  = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('total_days');

        return [
            Stat::make('Total Kuota Cuti Tahunan', "{$quota} Hari")
                ->description('Akumulasi semua jenis cuti')
                ->color('primary'),

            Stat::make('Cuti yang Digunakan', "{$used} Hari")
                ->description('Hanya yang disetujui')
                ->color('warning'),

            Stat::make('Sisa Cuti', ($quota - $used) . ' Hari')
                ->description('Bisa digunakan saat ini')
                ->color($used >= $quota ? 'danger' : 'success'),
        ];
    }
}
