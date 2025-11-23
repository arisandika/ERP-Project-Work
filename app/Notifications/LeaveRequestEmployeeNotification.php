<?php

namespace App\Notifications;

use App\Models\HR\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class LeaveRequestEmployeeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusReadable = match ($this->leaveRequest->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucwords($this->leaveRequest->status),
        };

        return [
            'leave_type' => $this->leaveRequest->leave?->leave_type,
            'status' => $statusReadable,
            'title' => 'Status Pengajuan Cuti',
            'body' => "Pengajuan cuti Anda telah {$statusReadable}.",
        ];
    }

    public static function sendFilamentNotification(LeaveRequest $leaveRequest, $recipient): void
    {
        $statusReadable = $leaveRequest->status === 'approved' ? 'Disetujui' : 'Ditolak';
        $type = $leaveRequest->status === 'approved' ? 'success' : 'danger';

        FilamentNotification::make()
            ->{$type}()
            ->title("Pengajuan Cuti {$statusReadable}")
            ->body("Pengajuan cuti Anda telah {$statusReadable}.")
            ->sendToDatabase($recipient);
    }
}
