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

    protected function afterSave(): void
    {
        // Jika statusnya disetujui, kita update data absensinya
        if ($this->record->status === 'approved') {
            
            $employee = $this->record->employee;
            $startDate = \Illuminate\Support\Carbon::parse($this->record->start_date);
            $endDate = \Illuminate\Support\Carbon::parse($this->record->end_date);

            // Looping dari start_date sampai end_date
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                
                // Lewati jika hari Sabtu / Minggu (opsional, sesuaikan dengan aturan kantor)
                if ($date->isWeekend()) {
                    continue;
                }

                /**
                 * Kita gunakan updateOrCreate.
                 * - Jika tanggal cuti HARI INI, dia akan mencari placeholder 'belum_presensi' lalu meng-update-nya jadi 'cuti'.
                 * - Jika tanggal cuti MINGGU DEPAN, dia akan membuatkan data absensi lebih awal dengan status 'cuti'.
                 */
                \App\Models\HR\Attendance::updateOrCreate([
                        'employee_id' => $employee->id,
                        'date'        => $date->toDateString(),
                    ],[
                        'shift_id'    => $employee->shift_id,
                        'status'      => 'cuti',
                        'note'        => 'Cuti: ' . $this->record->reason,
                    ]
                );
            }
        }
    }
}
