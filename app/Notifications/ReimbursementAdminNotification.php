<?php

namespace App\Notifications;

use App\Models\HR\ReimbursementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ReimbursementAdminNotification extends Notification
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
        return [
            'employee' => $this->reimbursement->employee?->full_name,
            'type' => $this->reimbursement->type,
            'amount' => $this->reimbursement->amount,
            'date' => $this->reimbursement->date,
            'status' => $this->reimbursement->status,
            'title' => 'Pengajuan Reimburse Baru',
            'body' => "{$this->reimbursement->employee?->full_name} mengajukan reimburse {$this->reimbursement->type}.",
        ];
    }

    public static function sendFilamentNotification(ReimbursementRequest $reimbursement, $recipient): void
    {
        FilamentNotification::make()
            ->info()
            ->title('Pengajuan Reimburse Baru')
            ->body(
                $reimbursement->employee?->full_name .
                ' mengajukan reimburse ' .
                $reimbursement->type .
                ' sebesar IDR ' . number_format($reimbursement->amount)
            )
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url('/reimburse-requests/' . $reimbursement->id)
                    ->button(),
            ])
            ->sendToDatabase($recipient);
    }
}