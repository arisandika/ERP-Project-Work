<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveApprovalOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->month;

        $stats = LeaveRequest::selectRaw("
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'rejected' AND MONTH(created_at) = ? THEN 1 END) as rejected_count
    ", [$currentMonth, $currentMonth])
            ->first();

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
        ];
    }
}
