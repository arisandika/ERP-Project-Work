<?php

namespace App\Console\Commands;

use App\Models\HR\LeaveRequest;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class LeaveAutoExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:auto-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengubah status pengajuan cuti pending yang sudah lewat tanggalnya menjadi expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Memulai pengecekan cuti kadaluwarsa per tanggal: " . $today->format('Y-m-d'));

        // Cari cuti yang statusnya 'pending' TAPI start_date-nya kurang dari hari ini
        // Artinya: Seharusnya cuti dimulai kemarin atau sebelumnya, tapi belum di-approve sampai sekarang.
        $expiredRequests = LeaveRequest::where('status', 'pending')
            ->whereDate('start_date', '<', $today)
            ->get();

        $count = 0;

        foreach ($expiredRequests as $request) {

            // 1. Update status jadi expired
            $request->update([
                'status' => 'expired'
            ]);

            // 2. Kirim Notifikasi ke Karyawan via Filament Database Notification
            // Agar karyawan tahu kenapa cutinya hilang
            try {
                $recipient = $request->employee->user;
                if ($recipient) {
                    Notification::make()
                        ->title('Pengajuan Cuti Kadaluwarsa')
                        ->body("Pengajuan cuti Anda untuk tanggal {$request->start_date->format('d M Y')} telah kadaluwarsa karena tidak disetujui hingga tanggal cuti dimulai.")
                        ->danger() // Merah
                        ->sendToDatabase($recipient);
                }
            } catch (\Exception $e) {
                // Ignore error notif user not found
            }

            $count++;
        }

        $this->info("Selesai. {$count} pengajuan cuti telah diubah menjadi expired.");
    }
}
