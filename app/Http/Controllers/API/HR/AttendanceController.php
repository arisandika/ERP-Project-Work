<?php
namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Storage;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Data karyawan tidak ditemukan.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $request->validate([
            'face_snapshot' => ['required', 'string'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $today = now()->toDateString();

        // Cek apakah karyawan sedang cuti
        $isOnLeave = \App\Models\HR\LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($isOnLeave) {
            Notification::make()
                ->title('Tidak Bisa Presensi')
                ->body('Kamu sedang dalam masa cuti. Tidak diperbolehkan presensi.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // NEW LOGIC: Cari placeholder presensi hari ini.
        // firstOrNew digunakan sebagai *fallback* jika seandainya cron job gagal jalan semalam, 
        // sistem tidak akan error dan akan membuat instancenya secara on-the-fly.
        $attendance = Attendance::firstOrNew(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'status' => 'belum_presensi',
                'shift_id' => $employee->shift_id,
            ]
        );

        // Cek apakah sudah presensi masuk (jam masuk sudah terisi)
        if ($attendance->exists && $attendance->clock_in) {
            Notification::make()
                ->title('Sudah Presensi Masuk')
                ->body('Kamu sudah melakukan presensi masuk hari ini.')
                ->warning()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // Ambil data shift dan toleransi waktu
        $shift = $employee->shift;
        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);
        $tolerance = $shift->tolerance_minutes ?? 0;
        $now = now();

        // Validasi lokasi (radius)
        $office = $employee->office;
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

        // Kondisi 1: bukan WFA dan di luar radius
        if ($isOutside && $employee->can_wfa != 1) {
            Notification::make()
                ->title('Tidak Bisa Presensi')
                ->body('Kamu berada di luar area kantor dan tidak memiliki izin WFA.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // Kondisi 2: bukan unlock shift dan tidak dalam range jam kerja
        $isOutsideShiftTime = $now->lt($shiftStart->subMinutes((int) $tolerance)) || $now->gt($shiftEnd->addMinutes((int) $tolerance));

        if ($isOutsideShiftTime && $employee->can_unlock_shift != 1) {
            Notification::make()
                ->title('Tidak Bisa Presensi')
                ->body('Kamu tidak memiliki izin untuk presensi di luar jam kerja.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        // Tentukan status (late / present)
        $status = $now->greaterThan($shiftStart->copy()->addMinutes((int) $tolerance))
            ? 'terlambat'
            : 'hadir';

        try {
            $photoInPath = $this->saveBase64Image(
                $request->face_snapshot,
                'attendances/in'
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Foto presensi tidak valid. Silakan ambil ulang foto.')
                ->danger()
                ->persistent()
                ->send();

            return redirect()->back();
        }

        // NEW LOGIC: UPDATE data attendance yang sudah ada (bukan Create lagi)
        $attendance->fill([
            'shift_id' => $shift->id,
            'note' => $request->note ?? $attendance->note,
            'clock_in' => now(),
            'latitude_in' => $request->lat,
            'longitude_in' => $request->lng,
            'face_snapshot_in' => $photoInPath,
            'status' => $status, // 'hadir' atau 'terlambat'
        ]);

        $attendance->save();

        // Notifikasi sukses
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

        if (!$employee) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Data karyawan tidak ditemukan.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $request->validate([
            'face_snapshot' => ['required', 'string'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $today = now()->toDateString();

        // LOGIC LAMA SUDAH BENAR: Mencari record presensi hari ini
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        // LOGIC LAMA SUDAH BENAR: Jika belum ada clock_in, tolak
        if (!$attendance || !$attendance->clock_in) {
            Notification::make()
                ->title('Belum Presensi Masuk')
                ->body('Kamu belum melakukan presensi masuk hari ini.')
                ->warning()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        if ($attendance->clock_out) {
            Notification::make()
                ->title('Sudah Presensi Keluar')
                ->body('Kamu sudah menyelesaikan presensi keluar hari ini.')
                ->info()
                ->send();
            return redirect()->back();
        }

        // Validasi lokasi (sama seperti check-in)
        $office = $employee->office;
        $distance = $this->getDistance(
            $office->latitude,
            $office->longitude,
            $request->lat,
            $request->lng
        );

        $isOutside = $distance > $office->radius_meters;

        if ($isOutside && $employee->can_wfa != 1 && $employee->can_unlock_shift != 1) {
            Notification::make()
                ->title('Tidak Bisa Presensi Keluar')
                ->body('Kamu berada di luar area kantor dan tidak memiliki izin WFA.')
                ->danger()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        $shift = $attendance->shift;

        if (!$shift || !$shift->end_time) {
            Notification::make()
                ->title('Shift Tidak Valid')
                ->body('Jam keluar shift tidak ditemukan.')
                ->danger()
                ->send();
            return redirect()->back();
        }

        // Gabungkan tanggal + jam keluar shift
        $shiftEndTime = Carbon::parse(
            $attendance->date->format('Y-m-d') . ' ' . $shift->end_time
        );

        // Jika sekarang masih sebelum jam keluar
        if (now()->lt($shiftEndTime)) {
            Notification::make()
                ->title('Belum Waktunya Presensi Keluar')
                ->body('Presensi keluar hanya bisa dilakukan setelah jam kerja selesai.')
                ->warning()
                ->persistent()
                ->send();
            return redirect()->back();
        }

        try {
            $photoOutPath = $this->saveBase64Image(
                $request->face_snapshot,
                'attendances/out'
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Presensi')
                ->body('Foto presensi keluar tidak valid. Silakan ambil ulang foto.')
                ->danger()
                ->persistent()
                ->send();

            return redirect()->back();
        }

        // LOGIC LAMA SUDAH BENAR: Ini tinggal mengupdate record yang sudah ada
        $attendance->update([
            'clock_out' => now(),
            'latitude_out' => $request->lat,
            'longitude_out' => $request->lng,
            'face_snapshot_out' => $photoOutPath,
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
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius; // jarak dalam meter
    }

    public function getMapData(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $officeId = $request->input('office_id');

        $query = Attendance::query()
            ->with(['employee.user', 'employee.department', 'employee.office', 'employee.shift'])
            ->whereDate('date', $date);

        if ($officeId) {
            $query->whereHas('employee.office', fn($q) => $q->where('id', $officeId));
        }

        $attendances = $query->get()->map(fn($item) => [
            'id' => $item->employee->id,
            'name' => $item->employee->full_name ?? 'Unknown',
            'photo' => $item->employee->photo
                ? Storage::url($item->employee->photo)
                : asset('assets/placeholder.jpg'),
            'department' => $item->employee->department->name ?? '-',
            'position' => $item->employee->position ?? '-',
            'lat' => $item->latitude_in,
            'lng' => $item->longitude_in,
            'clock_in' => optional($item->clock_in)->format('H:i') ?? '-',
            'clock_out' => optional($item->clock_out)->format('H:i') ?? '-',
            'status' => $item->status ?? '-',
            'can_wfa' => $item->can_wfa ?? false,
            'can_unlock_shift' => $item->can_unlock_shift ?? false,
            'office' => [
                'name' => $item->employee->office->name ?? '',
                'lat' => $item->employee->office->latitude ?? null,
                'lng' => $item->employee->office->longitude ?? null,
                'radius' => $item->employee->office->radius_meters ?? 0,
            ],
            'shift' => [
                'name' => $item->employee->shift->name ?? '',
                'start_time' => $item->employee->shift->start_time ?? '',
                'end_time' => $item->employee->shift->end_time ?? '',
            ],
            'face_snapshot_in' => $item->face_snapshot_in
                ? Storage::url($item->face_snapshot_in) : asset('assets/placeholder.jpg'),
            'face_snapshot_out' => $item->face_snapshot_out
                ? Storage::url($item->face_snapshot_out) : asset('assets/placeholder.jpg'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    private function saveBase64Image(string $base64, string $folder): string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            throw new \Exception('Format gambar tidak valid');
        }

        $extension = strtolower($type[1]); // jpeg, png, jpg
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);

        if ($data === false) {
            throw new \Exception('Gagal decode gambar');
        }

        $filename = $folder . '/' . uniqid() . '.' . $extension;

        Storage::disk('public')->put($filename, $data);

        return $filename;
    }
}
