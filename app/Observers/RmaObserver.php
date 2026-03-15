<?php

namespace App\Observers;

use App\Models\Inventory\Rma;

class RmaObserver
{
    public function saved(Rma $rma): void
    {
        // Tentukan status berdasarkan tanggal mana yang paling terakhir diisi
        if ($rma->returned_to_client_at) {
            $status = 'returned_to_client';
        } elseif ($rma->received_from_distributor_at) {
            $status = 'received_from_distributor';
        } elseif ($rma->sent_to_distributor_at) {
            $status = 'sent_to_distributor';
        } else {
            $status = 'received';
        }

        // Update status jika berubah (saveQuietly mencegah looping)
        if ($rma->status !== $status) {
            $rma->status = $status;
            $rma->saveQuietly();
        }
    }
}
