<?php

namespace App\Console\Commands\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredQuotations extends Command
{
    // Nama perintah yang akan dijalankan oleh cron
    protected $signature = 'sales:process-expired-quotations';
    protected $description = 'Otomatis mengubah status Quotation menjadi expired jika melewati valid_until';

    public function handle()
    {
        $this->info('Memulai pengecekan Quotation yang expired...');

        // Query efisien: Hanya cari yang statusnya masih 'draft' atau 'sent' dan sudah lewat tanggal
        $expiredQuotations = Quotation::whereIn('status', ['draft', 'sent'])
            ->whereDate('valid_until', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($expiredQuotations as $quotation) {
            // Gunakan updateQuietly agar tidak men-trigger event observer yang tidak perlu
            $quotation->updateQuietly(['status' => 'rejected', 'notes' => "Otomatis ditolak sistem karena expired pada " . $quotation->valid_until]);
            $count++;

            // Opsional: Dispatch Job untuk kirim email pemberitahuan ke pelanggan di sini
            // ExpiredQuotationNotificationJob::dispatch($quotation);
        }

        $this->info("Berhasil memproses {$count} Quotation menjadi expired.");
        Log::channel('sales')->info("Cron Job berjalan: {$count} Quotation expired diproses.");
    }
}
