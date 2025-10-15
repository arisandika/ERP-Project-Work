<?php
namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Data karyawan tidak ditemukan.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $today = now()->toDateString();

        // 🔍 Cek apakah sudah presensi hari ini
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            Notification::make()
                ->title('Sudah Presensi Masuk')
                ->body('Kamu sudah melakukan presensi masuk hari ini.')
                ->warning()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // 🕒 Ambil data shift dan toleransi
        $shift      = $employee->shift;
        $shiftStart = \Carbon\Carbon::parse($shift->start_time);
        $shiftEnd   = \Carbon\Carbon::parse($shift->end_time);
        $tolerance  = $shift->tolerance_minutes ?? 0;
        $now        = now();

        // 📍 Validasi lokasi (radius)
        $office    = $employee->office;
        $isOutside = false;

        if ($office && $office->latitude && $office->longitude) {
            $distance = $this->getDistance(
                $office->latitude,
                $office->longitude,
                $request->lat,
                $request->lng
            );

            $isOutside = $distance > $office->radius_meters;
        }

        // 🧭 Kondisi 1: bukan WFA dan di luar radius
        if ($isOutside && $employee->can_wfa != 1) {
            Notification::make()
                ->title('Tidak Bisa Presensi')
                ->body('Kamu berada di luar area kantor dan tidak memiliki izin WFA.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // 🕰️ Kondisi 2: bukan unlock shift dan tidak dalam rentang jam kerja
        $isOutsideShiftTime = $now->lt($shiftStart->subMinutes($tolerance)) || $now->gt($shiftEnd->addMinutes($tolerance));

        if ($isOutsideShiftTime && $employee->can_unlock_shift != 1) {
            Notification::make()
                ->title('Tidak Bisa Presensi')
                ->body('Kamu tidak memiliki izin untuk presensi di luar jam kerja.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // ⏰ Tentukan status (late / present)
        $status = $now->greaterThan($shiftStart->copy()->addMinutes($tolerance))
            ? 'late'
            : 'present';

        // 💾 Simpan data presensi
        Attendance::create([
            'employee_id'  => $employee->id,
            'shift_id'     => $shift->id,
            'date'         => $today,
            'note'         => $request->note,
            'clock_in'     => now(),
            'latitude_in'  => $request->lat,
            'longitude_in' => $request->lng,
            'status'       => $status,
        ]);

        // 🔔 Notifikasi sukses
        Notification::make()
            ->title('Berhasil Presensi Masuk')
            ->body("Kamu berhasil presensi masuk. Status kehadiran: " . ucfirst($status))
            ->success()
            ->persistent()
            ->send();

        return redirect()->back();
    }

    public function clockOut(Request $request)
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Data karyawan tidak ditemukan.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $today      = now()->toDateString();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            Notification::make()
                ->title('Belum Check-In')
                ->body('Kamu belum melakukan presensi masuk hari ini.')
                ->warning()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        if ($attendance->clock_out) {
            Notification::make()
                ->title('Sudah Check-Out')
                ->body('Kamu sudah menyelesaikan presensi keluar hari ini.')
                ->info()
                ->send();
            return redirect()->back();
        }

        // 💡 Validasi lokasi (sama seperti check-in)
        $office   = $employee->office;
        $distance = $this->getDistance(
            $office->latitude,
            $office->longitude,
            $request->lat,
            $request->lng
        );

        $isOutside = $distance > $office->radius_meters;

        if ($isOutside && $employee->can_wfa != 1 && $employee->can_unlock_shift != 1) {
            Notification::make()
                ->title('Tidak Bisa Check-Out')
                ->body('Kamu berada di luar area kantor dan tidak memiliki izin WFA.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $attendance->update([
            'clock_out'     => now(),
            'latitude_out'  => $request->lat,
            'longitude_out' => $request->lng,
        ]);

        Notification::make()
            ->title('Berhasil Presensi Keluar')
            ->body('Terima kasih! Kamu sudah menyelesaikan presensi hari ini.')
            ->success()
            ->persistent()
            ->send();

        return redirect()->back();
    }

    private function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters
        $latFrom     = deg2rad($lat1);
        $lonFrom     = deg2rad($lng1);
        $latTo       = deg2rad($lat2);
        $lonTo       = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius; // jarak dalam meter
    }

}
