<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    public function getTitle(): string
    {
        return 'Ajukan Cuti';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if (! $user?->employee) {
            abort(403, 'Super Admin tidak terhubung dengan data karyawan.');
        }

        $employee = $user->employee;

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $today = Carbon::today();

        // CEGAH CUTI DI TANGGAL YANG SUDAH LEWAT
        if ($start->lt($today)) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body('Tidak boleh mengajukan cuti pada tanggal yang sudah lewat.')
                ->danger()
                ->send();
            $this->halt();
        }

        // CEGAH START > END
        if ($start->gt($end)) {
            Notification::make()
                ->title("Tanggal tidak valid")
                ->body("Tanggal mulai tidak boleh lebih besar dari tanggal selesai.")
                ->danger()
                ->send();
            $this->halt();
        }

        // CEGAH PENGAJUAN CUTI DI WEEKEND (Sabtu & Minggu)
        $range = Carbon::parse($data['start_date'])
            ->range($data['end_date']); // iterable every day

        foreach ($range as $day) {
            if ($day->isWeekend()) {
                Notification::make()
                    ->title('Tanggal tidak valid')
                    ->body("Pengajuan cuti pada hari Sabtu atau Minggu tidak diperbolehkan.")
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        // CEGAH CUTI BENTROK (OVERLAP)
        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
            })
            ->exists();

        if ($overlap) {
            Notification::make()
                ->title('Tanggal bertabrakan')
                ->body('Anda sudah pernah mengajukan cuti pada rentang tanggal tersebut.')
                ->danger()
                ->send();
            $this->halt();
        }

        // HITUNG TOTAL HARI KERJA (tanpa weekend)
        $workingDays = $start->diffInDaysFiltered(
            fn (Carbon $date) => !$date->isWeekend(),
            $end
        );

        $data['total_days'] = $workingDays + 1;

        // VALIDASI KUOTA CUTI
        $leave = Leave::find($data['leave_id']);

        $used = LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_id', $data['leave_id'])
            ->where('status', 'approved')
            ->sum('total_days');

        $quota     = $leave->days_count;
        $remaining = $quota - $used;

        if ($data['total_days'] > $remaining) {
            Notification::make()
                ->title('Jatah cuti tidak mencukupi!')
                ->body("Sisa cuti Anda hanya {$remaining} hari.")
                ->danger()
                ->send();

            $this->halt();
        }

        // SET DEFAULT DATA REQUEST
        $data['employee_id'] = $employee->id;
        $data['status'] = 'pending';

        return $data;
    }
}
