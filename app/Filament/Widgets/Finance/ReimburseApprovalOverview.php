<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Finance\ReimbursementRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReimburseApprovalOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getStats(): array
    {
        $currentMonth = now()->month;

        $stats = ReimbursementRequest::selectRaw("
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_month_count,
            COUNT(CASE WHEN status = 'rejected' AND MONTH(created_at) = ? THEN 1 END) as rejected_month_count,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN amount ELSE 0 END) as approved_month_amount,
            SUM(amount) as total_amount
        ", [$currentMonth, $currentMonth, $currentMonth])
            ->first();

        return [
            Stat::make('Menunggu Approval', $stats->pending_count)
                ->description('Pengajuan belum diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([3, 5, 2, 7, 4, 6, $stats->pending_count]),

            Stat::make('Disetujui Bulan Ini', $stats->approved_month_count)
                ->description('Pengajuan disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([5, 8, 6, 10, 7, 9, $stats->approved_month_count]),

            Stat::make('Ditolak Bulan Ini', $stats->rejected_month_count)
                ->description('Pengajuan ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Nominal Pending', 'Rp ' . number_format($stats->pending_amount, 0, ',', '.'))
                ->description('Total menunggu')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Nominal Disetujui', 'Rp ' . number_format($stats->approved_month_amount, 0, ',', '.'))
                ->description('Cair bulan ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Total Reimburse', 'Rp ' . number_format($stats->total_amount, 0, ',', '.'))
                ->description('Seluruh pengajuan')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),
        ];
    }
}
