<?php

namespace App\Console\Commands;

use App\Models\HR\Attendance;
use App\Models\HR\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AttendanceMarkAbsent extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Menandai karyawan yang belum presensi sebagai Absen (Alpha) di akhir hari.';

    public function handle()
    {
        $today = Carbon::today();
        $this->info("Memulai finalisasi presensi untuk tanggal: " . $today->format('Y-m-d'));

        $isWeekend = $today->isWeekend();
        $holiday = Holiday::whereDate('date', $today)->first();

        // Base query — selalu exclude employee milik super_admin
        $baseQuery = Attendance::whereDate('date', $today)
            ->whereHas('employee.user', function ($query) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'super_admin');
                });
            });

        if ($isWeekend || $holiday) {
            $note = $isWeekend
                ? 'Libur Akhir Pekan (Sabtu/Minggu)'
                : 'Libur Nasional: ' . $holiday->name;

            $updatedCount = (clone $baseQuery)
                ->where('status', 'belum_presensi')
                ->update([
                    'status' => 'libur',
                    'note' => $note,
                ]);

            $this->info("Hari ini libur. {$updatedCount} data yang menggantung disesuaikan menjadi status 'libur'.");
            return 0;
        }

        $absentees = (clone $baseQuery)->where('status', 'belum_presensi');
        $count = $absentees->count();

        if ($count > 0) {
            $absentees->update([
                'status' => 'absen',
                'note' => 'Tidak Hadir Tanpa Keterangan',
            ]);
        }

        $this->info("Selesai. {$count} karyawan ditandai sebagai Absen (Alpha).");
        return 0;
    }
}