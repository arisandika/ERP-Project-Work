<?php
namespace App\Console\Commands;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AttendanceGeneratePlaceholders extends Command
{
    protected $signature   = 'attendance:generate-placeholders';
    protected $description = 'Membuat data presensi untuk semua karyawan aktif di awal hari.';

    public function handle()
    {
        $today = Carbon::today();
        $this->info("Memulai pembuatan placeholder untuk tanggal: " . $today->format('Y-m-d'));

        // Cek Kondisi Hari Ini
        $isWeekend = $today->isWeekend();
        $holiday   = Holiday::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        // Tentukan default status dan catatan
        $defaultStatus = 'belum_presensi';
        $defaultNote   = 'Menunggu presensi...';

        if ($isWeekend) {
            $defaultStatus = 'libur';
            $defaultNote   = 'Libur Akhir Pekan (Sabtu/Minggu)';
            $this->info("Hari ini akhir pekan. Menggenerate data dengan status 'libur'.");
        } elseif ($holiday) {
            $defaultStatus = 'libur';
            $defaultNote   = 'Libur Nasional: ' . $holiday->name;
            $this->info("Hari ini Libur Nasional ({$holiday->name}). Menggenerate data dengan status 'libur'.");
        }

        $employees      = Employee::where('status', 'active')->get();
        $generatedCount = 0;

        foreach ($employees as $employee) {
            Attendance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date'        => $today,
                ],
                [
                    'shift_id' => $employee->shift_id,
                    'status'   => $defaultStatus,
                    'note'     => $defaultNote,
                ]
            );
            $generatedCount++;
        }

        $this->info("Selesai. {$generatedCount} data presensi berhasil dibuat.");
        return 0;
    }
}
