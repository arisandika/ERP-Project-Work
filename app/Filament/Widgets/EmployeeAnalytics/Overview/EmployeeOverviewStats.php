<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Overview;

use App\Filament\Widgets\EmployeeAnalytics\Concerns\HasEmployeeFilter;
use App\Models\Finance\ReimbursementRequest;
use App\Models\HR\Attendance;
use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;

class EmployeeOverviewStats extends BaseWidget
{
    use HasEmployeeFilter;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected function getStats(): array
    {
        $employee = $this->getEmployee();
        $start = $this->getStartDate();
        $end = $this->getEndDate();
        $employeeId = $employee->id;

        // ── Semua query dalam 1 batch, tidak N+1 ──────────────────────────

        // Attendance periode
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get(['status', 'clock_in', 'clock_out']);

        $hadir = $attendances->whereIn('status', ['hadir', 'terlambat'])->count();
        $absen = $attendances->whereIn('status', ['absen'])->count();
        $terlambat = $attendances->where('status', 'terlambat')->count();

        // Rata-rata keterlambatan (menit) — hanya untuk yang terlambat
        // Butuh shift untuk tahu batas tepat waktu, kita estimasi dari clock_in > 08:00
        // Jika ada shift, bisa direfine
        $avgLateMinutes = 0;
        $lateAttendances = $attendances->where('status', 'terlambat');
        if ($lateAttendances->count() > 0) {
            $totalLateMinutes = $lateAttendances->sum(function ($a) {
                if (!$a->clock_in)
                    return 0;
                // Asumsi jam masuk standar 08:00 — sesuaikan dengan shift jika perlu
                $expected = Carbon::parse($a->clock_in->toDateString() . ' 08:00:00');
                return max(0, $a->clock_in->diffInMinutes($expected, false));
            });
            $avgLateMinutes = round($totalLateMinutes / $lateAttendances->count());
        }

        // Total jam kerja
        $totalWorkMinutes = $attendances->sum(function ($a) {
            if (!$a->clock_in || !$a->clock_out)
                return 0;
            return $a->clock_in->diffInMinutes($a->clock_out);
        });
        $totalWorkHours = round($totalWorkMinutes / 60, 1);

        // Sisa cuti — ambil semua leave type, hitung sisa
        // Leave quota ada di model Leave (days_count), usage dari LeaveRequest approved
        $leaves = Leave::all(['id', 'leave_type', 'days_count']);
        $usedPerType = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->selectRaw('leave_id, SUM(total_days) as used')
            ->groupBy('leave_id')
            ->pluck('used', 'leave_id');

        // Ambil leave type utama (tahunan = pertama, atau cari berdasarkan nama)
        $annualLeave = $leaves->first(fn($l) => str_contains(strtolower($l->leave_type), 'tahunan'))
            ?? $leaves->first();
        $annualSisa = $annualLeave
            ? max(0, $annualLeave->days_count - ($usedPerType[$annualLeave->id] ?? 0))
            : 0;

        // Pending reimburse
        $pendingReimburse = ReimbursementRequest::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Kehadiran', $hadir . ' hari')
                ->description("Tidak hadir: {$absen} hari")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success')
                ->chart(
                    $this->getAttendanceMiniChart($employeeId, $start, $end)
                ),
            Stat::make('Keterlambatan', $terlambat . 'x')
                ->description("Rata-rata: {$avgLateMinutes} menit")
                ->descriptionIcon('heroicon-m-clock')
                ->color($terlambat === 0 ? 'success' : ($terlambat <= 3 ? 'warning' : 'danger')),
            Stat::make('Total Jam Kerja', $totalWorkHours . ' jam')
                ->description('Periode yang dipilih')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            Stat::make('Sisa Cuti Tahunan', $annualSisa . ' hari')
                ->description($annualLeave ? "Kuota: {$annualLeave->days_count} hari/tahun" : 'Tidak ada kuota')
                ->descriptionIcon('heroicon-m-sun')
                ->color('info'),
            Stat::make('Reimburse Pending', $pendingReimburse . ' pengajuan')
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingReimburse > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * Mini sparkline chart — hadir per minggu dalam periode
     * Return array of int untuk Filament chart
     */
    private function getAttendanceMiniChart(int $employeeId, Carbon $start, Carbon $end): array
    {
        // Query per minggu, group by week number
        $data = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->selectRaw('WEEK(date) as week, COUNT(*) as count')
            ->groupBy('week')
            ->orderBy('week')
            ->pluck('count')
            ->toArray();

        return $data ?: [0];
    }
}
