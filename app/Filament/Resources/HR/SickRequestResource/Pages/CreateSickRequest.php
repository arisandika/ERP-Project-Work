<?php
namespace App\Filament\Resources\HR\SickRequestResource\Pages;

use App\Filament\Resources\HR\SickRequestResource;
use App\Models\HR\Attendance;
use App\Models\HR\SickRequest;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateSickRequest extends CreateRecord
{
    protected static string $resource = SickRequestResource::class;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan sakit');
        }
    }

    public function getTitle(): string
    {
        return 'Ajukan Sakit';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if (! $user?->employee) {
            abort(403, 'User tidak terhubung dengan data karyawan.');
        }

        $employee = $user->employee;

        /**
         * SAFE PARSE DATE
         */
        if (empty($data['start_date']) || empty($data['end_date'])) {
            Notification::make()->title('Tanggal wajib diisi')->danger()->send();
            $this->halt();
        }

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->startOfDay();
        $today = Carbon::today();

        /**
         * TIDAK BOLEH TANGGAL TERLALU LAMPAU
         * (Sakit boleh diajukan mundur 1-2 hari untuk susulan surat dokter,
         * silakan sesuaikan angka toleransi jika tidak diinginkan)
         */
        if ($start->lt($today->copy()->subDays(2))) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body('Pengajuan sakit maksimal untuk 2 hari ke belakang.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * START > END
         */
        if ($start->gt($end)) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * OVERLAP SAKIT (dengan pengajuan sakit lain)
         */
        $overlapSick = SickRequest::query()
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

        if ($overlapSick) {
            Notification::make()
                ->title('Tanggal bertabrakan')
                ->body('Anda sudah memiliki pengajuan sakit pada rentang tanggal tersebut.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * OVERLAP DENGAN CUTI (opsional, biar tidak dobel dengan LeaveRequest)
         */
        if (class_exists(\App\Models\HR\LeaveRequest::class)) {
            $overlapLeave = \App\Models\HR\LeaveRequest::query()
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

            if ($overlapLeave) {
                Notification::make()
                    ->title('Tanggal bertabrakan')
                    ->body('Anda sudah memiliki pengajuan cuti pada rentang tanggal tersebut.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        /**
         * HITUNG TOTAL HARI (Sakit dihitung hari kalender, bukan hari kerja)
         */
        $data['total_days'] = $start->diffInDays($end) + 1;

        /**
         * VALIDASI SUDAH ABSENSI (Jika sudah check-in, tidak boleh ajukan sakit)
         */
        $hasClockIn = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotNull('clock_in')
            ->exists();

        if ($hasClockIn) {
            Notification::make()
                ->title('Pengajuan Sakit Gagal')
                ->body('Anda tidak dapat mengajukan sakit karena Anda sudah melakukan presensi masuk (Check-in) pada rentang tanggal tersebut.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * Cek apakah status sudah 'absen' oleh sistem
         */
        $isMarkedAbsent = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('status', 'absen')
            ->exists();

        if ($isMarkedAbsent) {
            Notification::make()
                ->title('Pengajuan Sakit Ditolak')
                ->body('Anda tidak dapat mengajukan sakit karena sudah ditandai Absen (Alpha) pada tanggal tersebut.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * Cek jika jam kerja hari ini sudah selesai (untuk sakit mendadak, hanya berlaku jika start_date = hari ini)
         */
        if ($start->isToday()) {
            $shift = $employee->shift;

            if ($shift && $shift->end_time) {
                $shiftEndTime = Carbon::parse($shift->end_time);

                if (now()->gt($shiftEndTime)) {
                    Notification::make()
                        ->title('Waktu Pengajuan Habis')
                        ->body('Anda tidak dapat mengajukan sakit untuk hari ini karena jam kerja telah berakhir.')
                        ->danger()
                        ->send();
                    $this->halt();
                }
            }
        }

        /**
         * DEFAULT VALUE
         */
        $data['employee_id'] = $employee->id;
        $data['status']      = 'pending';

        return $data;
    }
}
