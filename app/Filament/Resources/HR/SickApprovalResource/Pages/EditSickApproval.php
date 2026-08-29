<?php

namespace App\Filament\Resources\HR\SickApprovalResource\Pages;

use App\Filament\Resources\HR\SickApprovalResource;
use App\Models\HR\Attendance;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSickApproval extends EditRecord
{
    protected static string $resource = SickApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Review Pengajuan Sakit';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $loggedEmployeeId = auth()->user()->employee->id ?? null;

        if ($data['status'] === 'approved') {

            if ($this->record->employee_id == $loggedEmployeeId) {

                Notification::make()
                    ->title('Persetujuan Sakit Ditolak')
                    ->body('Anda tidak diperbolehkan menyetujui pengajuan sakit Anda sendiri. Hubungi Atasan Anda untuk melakukan persetujuan.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['approved_by'] = $loggedEmployeeId ?? auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Jika statusnya disetujui, kita update data absensinya
        if ($this->record->status === 'approved') {

            $employee = $this->record->employee;
            $startDate = \Illuminate\Support\Carbon::parse($this->record->start_date);
            $endDate = \Illuminate\Support\Carbon::parse($this->record->end_date);

            // Looping dari start_date sampai end_date (sakit dihitung semua hari, termasuk weekend)
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

                /**
                 * Kita gunakan updateOrCreate.
                 * - Jika tanggal sakit HARI INI, dia akan mencari placeholder 'belum_presensi' lalu meng-update-nya jadi 'sakit'.
                 * - Jika tanggal sakit MINGGU DEPAN, dia akan membuatkan data absensi lebih awal dengan status 'sakit'.
                 */
                Attendance::updateOrCreate([
                        'employee_id' => $employee->id,
                        'date'        => $date->toDateString(),
                    ],[
                        'shift_id'    => $employee->shift_id,
                        'status'      => 'sakit',
                        'note'        => 'Sakit: ' . ($this->record->reason ?? '-'),
                    ]
                );
            }
        }
    }
}