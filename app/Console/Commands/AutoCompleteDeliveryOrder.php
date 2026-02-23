<?php

namespace App\Console\Commands;

use App\Models\Sales\DeliveryOrder;
use Illuminate\Console\Command;
use Carbon\Carbon;


class AutoCompleteDeliveryOrder extends Command
{
    protected $signature = 'do:auto-complete';
    protected $description = 'Selesaikan DO dan SO jika sudah diterima > 5 hari';

    public function handle()
    {
        $threshold = Carbon::now()->subDays(5);

        $dos = DeliveryOrder::where('status', 'delivered')
            ->where('updated_at', '<=', $threshold)
            ->get();

        $count = 0;
        foreach ($dos as $do) {
            // Bisa tambah status 'completed' ke DO jika kamu ada
            $do->update(['status' => 'completed']);

            // Selesaikan SO-nya
            if ($do->salesOrder && $do->salesOrder->status !== 'completed') {
                $do->salesOrder->update(['status' => 'completed']);
                $count++;
            }
        }

        $this->info("Berhasil meng-complete $count record otomatis.");
    }
}
