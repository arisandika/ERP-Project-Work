<?php

namespace App\Console\Commands;

use App\Models\HR\Attendance;
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

        // Cari semua record yang statusnya masih 'belum_presensi' hari ini
        $absentees = Attendance::whereDate('date', $today)
            ->where('status', 'belum_presensi');

        $count = $absentees->count();

        if ($count > 0) {
            $absentees->update([
                'status' => 'absen',
                'note' => 'Tidak Hadir Tanpa Keterangan',
            ]);
        }

        $this->info("Selesai. {$count} karyawan ditandai sebagai Absen.");
        return 0;
    }
}