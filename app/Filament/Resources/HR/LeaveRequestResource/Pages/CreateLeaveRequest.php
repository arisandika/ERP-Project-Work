<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan cuti');
        }
    }

    public function getTitle(): string
    {
        return 'Ajukan Cuti';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if (!$user?->employee) {
            abort(403, 'User tidak terhubung dengan data karyawan.');
        }

        $employee = $user->employee;

        /**
         * SAFE PARSE DATE
         */
        if (empty($data['start_date']) || empty($data['end_date'])) {
            Notification::make()
                ->title('Tanggal wajib diisi')
                ->danger()
                ->send();

            $this->halt();
        }

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $today = Carbon::today();

        /**
         * TIDAK BOLEH TANGGAL LAMPAU
         */
        if ($start->lt($today)) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body('Tidak boleh mengajukan cuti pada tanggal yang sudah lewat.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * START > END
         */
        if ($start->gt($end)) {
            Notification::make()
                ->title("Tanggal tidak valid")
                ->body("Tanggal mulai tidak boleh lebih besar dari tanggal selesai.")
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * WEEKEND
         */
        if ($start->isWeekend() || $end->isWeekend()) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body("Tanggal tidak boleh jatuh pada Sabtu atau Minggu.")
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * OVERLAP CUTI
         */
        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
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
                ->body('Anda sudah memiliki pengajuan cuti pada rentang tanggal tersebut.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * HITUNG HARI KERJA
         */
        $workingDays = $start->diffInDaysFiltered(
            fn(Carbon $date) => !$date->isWeekend(),
            $end
        );

        $data['total_days'] = $workingDays + 1;


        /**
         * VALIDASI JENIS CUTI
         */
        if (empty($data['leave_id'])) {

            Notification::make()
                ->title('Jenis cuti wajib dipilih')
                ->danger()
                ->send();

            $this->halt();
        }

        $leave = Leave::find($data['leave_id']);

        if (!$leave) {

            Notification::make()
                ->title('Jenis cuti tidak ditemukan')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * HITUNG PEMAKAIAN CUTI
         */
        $used = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_id', $leave->id)
            ->where('status', 'approved')
            ->sum('total_days');


        $remaining = $leave->days_count - $used;

        /**
         * VALIDASI KUOTA
         */
        if ($data['total_days'] > $remaining) {

            Notification::make()
                ->title('Jatah cuti tidak mencukupi')
                ->body("Sisa cuti Anda {$remaining} hari.")
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * DEFAULT VALUE
         */
        $data['employee_id'] = $employee->id;
        $data['status'] = 'pending';

        return $data;
    }
}
