<?php

namespace App\Console\Commands;

use App\Models\HR\Attendance;
use App\Models\HR\Holiday; // <-- Import model Holiday
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

        // Jangan proses Alpha jika hari ini Sabtu atau Minggu
        if ($today->isWeekend()) {
            $this->info('Hari ini weekend, tidak ada proses alpha yang dijalankan.');
            return 0;
        }

        // Jangan proses Alpha jika hari ini adalah Hari Libur
        $holiday = Holiday::whereDate('date', $today)->first();
        if ($holiday) {
            $this->info("Hari ini adalah hari libur ({$holiday->name}), tidak ada proses alpha yang dijalankan.");
            return 0;
        }

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