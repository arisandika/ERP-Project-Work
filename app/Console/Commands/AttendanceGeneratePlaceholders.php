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

        if ($isWeekend || $holiday) {
            $description = $isWeekend
                ? 'akhir pekan (Sabtu/Minggu)'
                : 'hari libur nasional (' . $holiday->name . ')';

            $this->info("Hari ini {$description}. Tidak ada placeholder yang dibuat.");
            return 0;
        }

        $employees = Employee::where('status', 'active')
            ->forAttendanceReporting()
            ->get();
        $generatedCount = 0;

        foreach ($employees as $employee) {
            Attendance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date'        => $today,
                ],
                [
                    'shift_id' => $employee->shift_id,
                    'status'   => 'belum_presensi',
                    'note'     => 'Menunggu presensi...',
                ]
            );
            $generatedCount++;
        }

        $this->info("Selesai. {$generatedCount} data presensi berhasil dibuat.");
        return 0;
    }
}
