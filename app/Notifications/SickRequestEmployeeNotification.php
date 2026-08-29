<?php
namespace App\Notifications;

use App\Models\HR\SickRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SickRequestEmployeeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SickRequest $sickRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusReadable = match ($this->sickRequest->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => ucwords($this->sickRequest->status),
        };

        return [
            'status' => $statusReadable,
            'title'  => 'Status Pengajuan Sakit',
            'body'   => "Pengajuan izin sakit Anda telah {$statusReadable}.",
        ];
    }

    public static function sendFilamentNotification(SickRequest $sickRequest, $recipient): void
    {
        $statusReadable = $sickRequest->status === 'approved' ? 'Disetujui' : 'Ditolak';
        $type           = $sickRequest->status === 'approved' ? 'success' : 'danger';

        FilamentNotification::make()
            ->{$type}()
            ->title("Pengajuan Sakit {$statusReadable}")
            ->body("Pengajuan izin sakit Anda telah {$statusReadable}.")
            ->sendToDatabase($recipient);
    }
}
