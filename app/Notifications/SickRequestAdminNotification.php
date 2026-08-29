<?php
namespace App\Notifications;

use App\Models\HR\SickRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SickRequestAdminNotification extends Notification
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
        return [
            'employee'   => $this->sickRequest->employee?->full_name,
            'start_date' => $this->sickRequest->start_date,
            'end_date'   => $this->sickRequest->end_date,
            'total_days' => $this->sickRequest->total_days,
            'status'     => $this->sickRequest->status,
            'title'      => 'Pengajuan Sakit Baru',
            'body'       => "{$this->sickRequest->employee?->full_name} mengajukan izin sakit ({$this->sickRequest->total_days} hari).",
        ];
    }

    public static function sendFilamentNotification(SickRequest $sickRequest, $recipient): void
    {
        FilamentNotification::make()
            ->info()
            ->title('Pengajuan Sakit Baru')
            ->body($sickRequest->employee?->full_name . ' mengajukan izin sakit (' . $sickRequest->total_days . ' hari)')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url('/hr/sick-approvals/' . $sickRequest->id)
                    ->button(),
            ])
            ->sendToDatabase($recipient);
    }
}
