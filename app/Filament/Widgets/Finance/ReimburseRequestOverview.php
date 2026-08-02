<?php
namespace App\Filament\Widgets\Finance;

use App\Models\Finance\ReimbursementRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReimburseRequestOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ! $user->hasRole('manager_finance') && $user->employee !== null;
    }

    protected function getStats(): array
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return [];
        }

        $monthStart   = now()->startOfMonth()->toDateString();
        $monthEnd     = now()->endOfMonth()->toDateString();
        $currentMonth = now()->month;

        $stats = ReimbursementRequest::where('employee_id', $employee->id)
            ->selectRaw("
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'approved' AND MONTH(created_at) = ? THEN 1 END) as approved_month_count,
                SUM(CASE WHEN date BETWEEN ? AND ? AND status = 'approved' THEN amount ELSE 0 END) as monthly_total,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total
            ", [$currentMonth, $monthStart, $monthEnd])
            ->first();

        return [
            Stat::make('Menunggu', $stats->pending_count)
                ->description('Pengajuan diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([1, 0, 2, 1, 0, 3, $stats->pending_count]),

            Stat::make('Disetujui Bulan Ini', $stats->approved_month_count)
                ->description('Reimburse cair')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Nominal Pending', 'Rp ' . number_format($stats->pending_total, 0, ',', '.'))
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Cair Bulan Ini', 'Rp ' . number_format($stats->monthly_total, 0, ',', '.'))
                ->description('Total diterima')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
