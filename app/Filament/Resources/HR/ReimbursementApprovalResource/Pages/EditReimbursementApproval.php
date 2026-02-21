<?php

namespace App\Filament\Resources\HR\ReimbursementApprovalResource\Pages;

use App\Filament\Resources\HR\ReimbursementApprovalResource;
use App\Models\Finance\FinancialRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReimbursementApproval extends EditRecord
{
    protected static string $resource = ReimbursementApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Review Pengajuan Reimburse';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $loggedEmployeeId = auth()->user()->employee->id ?? null;

        /**
         * Jika approve
         */
        if ($data['status'] === 'approved') {

            /**
             * Tidak boleh approve sendiri
             */
            if ($this->record->employee_id == $loggedEmployeeId) {

                Notification::make()
                    ->title('Persetujuan Reimburse Ditolak')
                    ->body('Anda tidak diperbolehkan menyetujui pengajuan reimburse Anda sendiri.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['approved_by'] = $loggedEmployeeId;
            $data['approved_at'] = now();
        }

        return $data;
    }

    // Setelah save → masuk Finance
    protected function afterSave(): void
    {
        $record = $this->record;

        // Jika approved
        if ($record->status === 'approved') {

            // Hindari double insert
            FinancialRecord::firstOrCreate(

                [
                    'reimburse_id' => $record->id
                ],

                [
                    'transaction_date' => $record->date,

                    'type' => 'pengeluaran',

                    'description' =>
                        'Reimburse - ' .
                        $record->employee->full_name .
                        ' - ' .
                        $record->type,

                    'amount' => $record->amount,

                    'category' => 'Operasional',
                ]
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
