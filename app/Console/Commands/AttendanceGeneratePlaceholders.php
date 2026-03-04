<?php

namespace App\Console\Commands;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AttendanceGeneratePlaceholders extends Command
{
    protected $signature = 'attendance:generate-placeholders';
    protected $description = 'Membuat data presensi kosong (placeholder) untuk semua karyawan aktif di awal hari.';

    public function handle()
    {
        $today = Carbon::today();
        $this->info("Memulai pembuatan placeholder untuk tanggal: " . $today->format('Y-m-d'));

        // Jangan generate di hari Sabtu atau Minggu
        if ($today->isWeekend()) {
            $this->info('Hari ini weekend, tidak ada placeholder yang dibuat.');
            return 0;
        }

        $employees = Employee::where('status', 'Aktif')->get();
        $generatedCount = 0;

        foreach ($employees as $employee) {
            // Gunakan firstOrCreate untuk mencegah duplikat jika command dijalankan ulang
            Attendance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $today,
                ],
                [
                    'shift_id' => $employee->shift_id,
                    'status' => 'belum_presensi',
                    'note' => 'Menunggu presensi...'
                ]
            );
            $generatedCount++;
        }

        $this->info("Selesai. {$generatedCount} placeholder presensi berhasil dibuat.");
        return 0;
    }
}