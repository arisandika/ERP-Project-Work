<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Models\HR\Attendance;
use App\Models\HR\Holiday;
use App\Models\HR\Leave;
use App\Models\HR\LeaveRequest;
use Carbon\CarbonPeriod; // <-- Tambahkan ini
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
         * LOGIC BARU: HITUNG HARI KERJA EFEKTIF (Mengecualikan Weekend & Libur)
         */
        $leaveDays = 0;
        $period = CarbonPeriod::create($start, $end);

        // Ambil array tanggal libur nasional di rentang waktu tersebut
        $holidays = Holiday::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Opsional: Jika jenis cutinya adalah 'Melahirkan' atau 'Haji', biasanya aturannya hari kalender (tanpa potong libur)
        // Silakan sesuaikan nama cuti di regex ini jika ada pengecualian.
        $isFullCalendar = preg_match('/melahirkan|haji/i', $leave->name);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');

            if ($isFullCalendar) {
                $leaveDays++; // Dihitung semua hari jika cuti khusus
            } else {
                $isWeekend = $date->isWeekend();
                $isHoliday = in_array($dateString, $holidays);

                // Hitung sebagai 1 hari cuti JIKA BUKAN weekend dan BUKAN libur nasional
                if (!$isWeekend && !$isHoliday) {
                    $leaveDays++;
                }
            }
        }

        // Masukkan hasil perhitungan ke form data
        $data['total_days'] = $leaveDays;

        /**
         * VALIDASI TOTAL HARI (Jika user cuma pilih tanggal pas hari libur/weekend)
         */
        if ($data['total_days'] === 0) {
            Notification::make()
                ->title('Tanggal tidak valid')
                ->body('Rentang tanggal yang dipilih hanya berisi Hari Libur atau Akhir Pekan (Weekend).')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * HITUNG PEMAKAIAN CUTI & VALIDASI KUOTA
         */
        $used = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_id', $leave->id)
            ->where('status', 'approved')
            ->sum('total_days');

        $remaining = $leave->days_count - $used;

        if ($data['total_days'] > $remaining) {
            Notification::make()
                ->title('Jatah cuti tidak mencukupi')
                ->body("Pengajuan cuti ini memotong {$data['total_days']} hari kerja, namun sisa cuti Anda hanya {$remaining} hari.")
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * VALIDASI SUDAH ABSENSI
         */
        $hasClockIn = Attendance::query()
            ->where('employee_id', $employee->id)
            // Format ke Y-m-d agar query DB akurat murni hanya mencocokkan tanggal
            ->whereBetween('date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            ])
            // Mengecek apakah jam masuk sudah terisi (sudah absen)
            ->whereNotNull('clock_in')
            ->exists();

        if ($hasClockIn) {
            Notification::make()
                ->title('Pengajuan Cuti Gagal')
                ->body('Anda tidak dapat mengajukan cuti karena Anda sudah melakukan presensi masuk (Check-in) pada rentang tanggal tersebut.')
                ->danger()
                ->send();

            $this->halt();
        }

        /**
         * Cek apakah status sudah 'absen' oleh sistem
         */
        $isMarkedAbsent = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            ])
            ->where('status', 'absen')
            ->exists();

        if ($isMarkedAbsent) {
            Notification::make()
                ->title('Pengajuan Cuti Ditolak')
                ->body('Anda tidak dapat mengajukan cuti karena sudah ditandai Absen (Alpha) pada tanggal tersebut.')
                ->danger()
                ->send();
            $this->halt();
        }

        /**
         * Cek jika jam kerja hari ini sudah selesai (Untuk cuti mendadak)
         */
        if ($start->isToday()) {
            $shift = $employee->shift;

            if ($shift && $shift->end_time) {
                $shiftEndTime = Carbon::parse($shift->end_time);

                if (now()->gt($shiftEndTime)) {
                    Notification::make()
                        ->title('Waktu Pengajuan Habis')
                        ->body('Anda tidak dapat mengajukan cuti untuk hari ini karena jam kerja telah berakhir.')
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
        $data['status'] = 'pending';

        return $data;
    }
}