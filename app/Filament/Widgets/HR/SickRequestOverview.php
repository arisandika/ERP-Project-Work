<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\SickRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SickRequestOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user     = auth()->user();
        $employee = $user?->employee;

        if (! $employee) {
            return [];
        }

        $currentMonth = now()->month;

        // Hitung pending/approved/rejected khusus milik employee ini
        $stats = SickRequest::where('employee_id', $employee->id)
            ->selectRaw("
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' AND MONTH(created_at) = ? THEN 1 END) as rejected_count
            ", [$currentMonth, $currentMonth])
            ->first();

        // Total hari sakit yang sudah disetujui tahun ini
        $usedThisYear = SickRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        return [
            Stat::make('Menunggu Approval', $stats->pending_count)
                ->description('Jumlah pengajuan belum diproses')
                ->color('warning'),

            Stat::make('Disetujui Bulan Ini', $stats->approved_count)
                ->description('Jumlah pengajuan disetujui')
                ->color('success'),

            Stat::make('Ditolak Bulan Ini', $stats->rejected_count)
                ->description('Jumlah pengajuan ditolak')
                ->color('danger'),

            Stat::make('Total Hari Sakit', "{$usedThisYear} Hari")
                ->description('Terhitung dari pengajuan disetujui tahun ini')
                ->color('info'),
        ];
    }
}
