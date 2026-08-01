<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Finance\ReimbursementRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReimburseApprovalOverview extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $stats = ReimbursementRequest::selectRaw("
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_month_count,
        COUNT(CASE WHEN status = 'rejected' AND MONTH(created_at) = ? THEN 1 END) as rejected_month_count,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN amount ELSE 0 END) as approved_month_amount,
        SUM(amount) as total_amount
    ", [now()->month, now()->month, now()->month])
            ->first();

        return [
            Stat::make('Menunggu Approval', $stats->pending_count)
                ->description('Jumlah pengajuan belum diproses')
                ->color('warning'),

            Stat::make('Disetujui Bulan Ini', $stats->approved_month_count)
                ->description('Jumlah pengajuan disetujui')
                ->color('success'),

            Stat::make('Ditolak Bulan Ini', $stats->rejected_month_count)
                ->description('Jumlah pengajuan ditolak')
                ->color('danger'),

            Stat::make('Nominal Pending', 'IDR ' . number_format($stats->pending_amount))
                ->description('Total nominal reimburse menunggu')
                ->color('warning'),

            Stat::make('Nominal Disetujui Bulan Ini', 'IDR ' . number_format($stats->approved_month_amount))
                ->description('Total nominal reimburse disetujui')
                ->color('success'),

            Stat::make('Nominal Total', 'IDR ' . number_format($stats->total_amount))
                ->description('Total seluruh reimburse')
                ->color('primary'),
        ];
    }
}
