<?php

namespace App\Notifications;

use App\Models\Finance\ReimbursementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ReimbursementEmployeeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ReimbursementRequest $reimbursement
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusReadable = match ($this->reimbursement->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucwords($this->reimbursement->status),
        };

        return [
            'type' => $this->reimbursement->type,
            'amount' => $this->reimbursement->amount,
            'status' => $statusReadable,
            'title' => 'Status Pengajuan Reimburse',
            'body' => "Pengajuan reimburse {$this->reimbursement->type} Anda telah {$statusReadable}.",
        ];
    }

    public static function sendFilamentNotification(ReimbursementRequest $reimbursement, $recipient): void
    {
        $statusReadable = $reimbursement->status === 'approved' ? 'Disetujui' : 'Ditolak';
        $type = $reimbursement->status === 'approved' ? 'success' : 'danger';

        FilamentNotification::make()
            ->{$type}()
                ->title("Pengajuan Reimburse {$statusReadable}")
                ->body("Reimburse {$reimbursement->type} sebesar IDR " . number_format($reimbursement->amount) . " telah {$statusReadable}.")
                ->sendToDatabase($recipient);
    }
}