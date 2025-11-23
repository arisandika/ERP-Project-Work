<?php

namespace App\Notifications;

use App\Models\HR\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class LeaveRequestAdminNotification extends Notification
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
        return [
            'employee' => $this->leaveRequest->employee?->full_name,
            'leave_type' => $this->leaveRequest->leave?->leave_type,
            'start_date' => $this->leaveRequest->start_date,
            'end_date' => $this->leaveRequest->end_date,
            'status' => $this->leaveRequest->status,
            'title' => 'Pengajuan Cuti Baru',
            'body' => "{$this->leaveRequest->employee?->full_name} mengajukan cuti ({$this->leaveRequest->leave?->leave_type}).",
        ];
    }

    public static function sendFilamentNotification(LeaveRequest $leaveRequest, $recipient): void
    {
        FilamentNotification::make()
            ->info()
            ->title('Pengajuan Cuti Baru')
            ->body($leaveRequest->employee?->full_name . ' mengajukan cuti ' . $leaveRequest->leave?->leave_type)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url('/leave-approvals/' . $leaveRequest->id)
                    ->button(),
            ])
            ->sendToDatabase($recipient);
    }
}
