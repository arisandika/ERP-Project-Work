<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveRequestOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $employee = $user?->employee;

        if (!$employee) {
            return [];
        }

        // Query leave, filter untuk gender jika perlu
        $leavesQuery = Leave::query()
            ->when($employee->gender !== 'Perempuan', function ($q) {
                $q->where('is_female_only', false);
            });

        // Ambil sekaligus quota total dan jumlah jenis cuti
        $leaveStats = $leavesQuery
            ->selectRaw('SUM(days_count) as total_quota, COUNT(*) as types_count')
            ->first();

        $quota = $leaveStats->total_quota ?? 0;
        $types = $leaveStats->types_count ?? 0;

        // Hitung total cuti yang sudah digunakan oleh karyawan
        $used = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('total_days');

        $remaining = max($quota - $used, 0);

        // Untuk Stat approval, bisa gunakan 0 jika $stats belum ada
        $stats = (object) [
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
        ];

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

            Stat::make('Sisa Cuti', "{$remaining} Hari")
                ->description('Total cuti yang masih tersedia')
                ->color('success'),

            Stat::make('Cuti Digunakan', "{$used} Hari")
                ->description('Cuti yang sudah disetujui')
                ->color('warning'),

            Stat::make('Jenis Cuti', "{$types} Jenis")
                ->description('Jenis cuti yang tersedia')
                ->color('primary'),
        ];
    }
}
