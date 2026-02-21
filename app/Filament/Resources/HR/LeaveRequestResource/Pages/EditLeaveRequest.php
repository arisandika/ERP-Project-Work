<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use Filament\Actions;
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
                ->title('Pengajuan cuti yang telah disetujui tidak dapat diedit.')
                ->danger()
                ->send();

            return $this->getResource()::getUrl('index');
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
