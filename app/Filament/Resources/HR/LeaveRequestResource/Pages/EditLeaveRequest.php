<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan cuti');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(LeaveRequest $record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function ($record) {

                    $employeeId = auth()->user()?->employee?->id;

                    if (!$record->canBeCancelledBy($employeeId)) {
                        abort(403);
                    }

                    $record->cancel();
                })
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Pengajuan Cuti';
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'approved_at' => now(),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status === 'approved') {

            Notification::make()
                ->title('Pengajuan yang telah disetujui tidak dapat diedit.')
                ->danger()
                ->send();

            redirect(
                $this->getResource()::getUrl('index')
            );

            return $data;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status === 'approved') {
            Notification::make()
                ->title('Pengajuan yang sudah disetujui tidak boleh diubah.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
