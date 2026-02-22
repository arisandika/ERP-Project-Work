<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\ReimbursementRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReimburseRequestOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $employee = auth()->user()?->employee;

        if (!$employee) {
            return [];
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $currentMonth = now()->month;

        // Query tunggal, semua agregasi
        $stats = ReimbursementRequest::where('employee_id', $employee->id)
            ->selectRaw("
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_month_count,
            COUNT(CASE WHEN status = 'rejected' AND MONTH(created_at) = ? THEN 1 END) as rejected_month_count,
            SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END) as monthly_total,
            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_total,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total
        ", [$currentMonth, $currentMonth, $monthStart, $monthEnd])
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

            Stat::make('Nominal Pending', 'IDR ' . number_format($stats->pending_total))
                ->description('Total nominal reimburse menunggu')
                ->color('warning'),

            Stat::make('Nominal Disetujui Bulan Ini', 'IDR ' . number_format($stats->monthly_total))
                ->description('Total nominal reimburse disetujui')
                ->color('success'),

            Stat::make('Nominal Total', 'IDR ' . number_format($stats->approved_total))
                ->description('Total seluruh reimburse')
                ->color('primary'),
        ];
    }
}
