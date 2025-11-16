<?php
namespace App\Filament\Resources\HR\LeaveApprovalResource\Pages;

use App\Filament\Resources\HR\LeaveApprovalResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLeaveApproval extends EditRecord
{
    protected static string $resource = LeaveApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Review Pengajuan Cuti';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $loggedEmployeeId = auth()->user()->employee->id ?? null;

        if ($data['status'] === 'approved') {

            if ($this->record->employee_id == $loggedEmployeeId) {

                Notification::make()
                    ->title('Persetujuan Cuti Ditolak')
                    ->body('Anda tidak diperbolehkan menyetujui pengajuan cuti Anda sendiri. Hubungi Atasan Anda untuk melakukan persetujuan.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['approved_by'] = $loggedEmployeeId ?? auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
