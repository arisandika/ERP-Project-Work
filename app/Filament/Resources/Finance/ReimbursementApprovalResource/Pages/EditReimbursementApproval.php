<?php

namespace App\Filament\Resources\Finance\ReimbursementApprovalResource\Pages;

use App\Filament\Resources\Finance\ReimbursementApprovalResource;
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

    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->status === 'approved') {

            // Hindari double insert
            FinancialRecord::firstOrCreate(
                [
                    'reimburse_id' => $record->id,
                    'created_by' => $record->employee_id
                ],
                [
                    'transaction_date' => $record->date,
                    'type' => 'pengeluaran',
                    'description' =>
                    'Reimburse - ' . $record->type,
                    'amount' => $record->amount,
                    'category' => $record->type,
                    'receipt' => $record->receipt,
                ]
            );
        }
    }
}
