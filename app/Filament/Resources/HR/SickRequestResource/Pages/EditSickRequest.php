<?php
namespace App\Filament\Resources\HR\SickRequestResource\Pages;

use App\Filament\Resources\HR\SickRequestResource;
use App\Models\HR\SickRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSickRequest extends EditRecord
{
    protected static string $resource = SickRequestResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan sakit');
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(SickRequest $record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $employeeId = auth()->user()?->employee?->id;

                    if (! $employeeId) {
                        Notification::make()->title('Data karyawan tidak ditemukan')->danger()->send();
                        return;
                    }

                    if ($record->employee_id !== $employeeId) {
                        Notification::make()->title('Anda tidak bisa membatalkan pengajuan orang lain')->danger()->send();
                        return;
                    }

                    if (! in_array($record->status, ['pending', 'approved'])) {
                        Notification::make()->title('Status pengajuan tidak bisa dibatalkan')->warning()->send();
                        return;
                    }

                    $record->update(['status' => 'cancelled']);

                    Notification::make()->title('Pengajuan berhasil dibatalkan')->success()->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Pengajuan Sakit';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status === 'approved') {
            Notification::make()
                ->title('Pengajuan yang telah disetujui tidak dapat diedit.')
                ->danger()
                ->send();

            redirect($this->getResource()::getUrl('index'));

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
