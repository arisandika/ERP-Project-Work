<?php

namespace App\Filament\Pages;

use App\Models\HR\Attendance;
use App\Models\HR\Holiday;
use App\Models\HR\LeaveRequest;
use App\Support\ModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleSelector extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string $view = 'filament.pages.module-selector';
    protected static ?string $title = 'Pilih Modul';
    protected static ?string $slug = 'modules';
    protected static bool $shouldRegisterNavigation = false;

    // Computed properties 
    public ?array $attendanceInfo = null;
    public array $pillData = [];

    public function mount(): void
    {
        $this->attendanceInfo = $this->resolveAttendanceInfo();
        $this->pillData = $this->resolvePillData();
    }

    public function getModules(): Collection
    {
        return ModuleAccess::availableModules();
    }

    /**
     * Resolusi Data untuk Banner Peringatan (Alert) Presensi
     */
    private function resolveAttendanceInfo(): ?array
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('super_admin')) {
            return null;
        }

        $employee = $user->employee;

        if (!$employee) {
            return [
                'message' => 'Data karyawan Anda belum diatur di sistem HR. Silakan hubungi Administrator.',
                'type' => 'danger',
            ];
        }

        if (now()->isWeekend()) {
            return [
                'message' => 'Hari ini adalah akhir pekan. Sistem presensi reguler ditutup.',
                'type' => 'success',
            ];
        }

        $today = now()->toDateString();

        $holiday = Cache::remember(
            "holiday:{$today}",
            now()->endOfDay(),
            fn() =>
            Holiday::whereDate('date', $today)->first(['id', 'name'])
        );

        if ($holiday) {
            return [
                'message' => "Hari ini libur: <strong>{$holiday->name}</strong>. Sistem presensi ditutup, selamat beristirahat!",
                'type' => 'success',
            ];
        }

        $isOnLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($isOnLeave) {
            return [
                'message' => 'Kamu sedang dalam masa <strong>Cuti</strong> hari ini. Selamat menikmati waktu luangmu!',
                'type' => 'success',
            ];
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first(['clock_in', 'clock_out']);

        if (!$attendance?->clock_in) {
            return [
                'message' => 'Kamu belum melakukan <a href="/attendance" class="font-bold underline transition-all hover:underline-offset-4">Presensi Masuk</a> hari ini. Jangan lupa absen ya!',
                'type' => 'warning',
            ];
        }

        if (!$attendance->clock_out) {
            return [
                'message' => 'Kamu sudah presensi masuk. Jangan lupa <a href="/attendance" class="font-bold underline transition-all hover:underline-offset-4">Presensi Keluar</a> saat jam kerja selesai.',
                'type' => 'info',
            ];
        }

        return [
            'message' => 'Kamu sudah menyelesaikan presensi hari ini. Terima kasih atas kerja kerasmu!',
            'type' => 'success',
        ];
    }

    /**
     * Resolusi Data untuk Info Pills (Kapsul Status di Bawah Ucapan Selamat Datang)
     */
    private function resolvePillData(): array
    {
        $user = auth()->user();
        $employee = $user?->employee;
        $today = now()->toDateString();

        $data = [
            'is_weekend' => now()->isWeekend(),
            'shift' => null,
            'upcoming_leave' => null,
            'upcoming_holiday' => null,
            'department' => null,
            'role' => null,
        ];

        if (!$user || $user->hasRole('super_admin') || !$employee) {
            return $data;
        }

        // --- 1. Ambil Departemen & Role ---
        // Load relasi department
        $employee->loadMissing('department');
        $data['department'] = $employee->department?->name ?? 'Belum ada departemen';

        // Format nama Role (Misal: "hr_employees" -> "HR Employees")
        $roles = $user->roles->pluck('name')->map(function ($role) {
            $formatted = ucwords(str_replace('_', ' ', $role));
            // Perbaiki beberapa singkatan (Opsional, agar 'Hr' menjadi 'HR')
            $formatted = str_replace(['Hr ', 'Crm ', 'It '], ['HR ', 'CRM ', 'IT '], $formatted);
            return $formatted;
        })->filter()->implode(', ');

        $data['role'] = $roles ?: 'Tidak ada role';


        // --- 2. Ambil data Shift ---
        if ($employee->shift_id) {
            $employee->loadMissing('shift');
            if ($employee->shift) {
                $start = \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i');
                $end = \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i');
                $data['shift'] = "{$employee->shift->name} ({$start} - {$end})";
            }
        }

        // --- 3. Ambil Cuti yang sedang berjalan atau akan datang ---
        $cuti = \App\Models\HR\LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first(['start_date', 'end_date']);

        if ($cuti) {
            $data['upcoming_leave'] = [
                'start' => $cuti->start_date,
                'end' => $cuti->end_date,
            ];
        }

        // --- 4. Ambil Libur Nasional 30 Hari Kedepan ---
        $holiday = \App\Models\HR\Holiday::whereDate('date', '>=', $today)
            ->whereDate('date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('date')
            ->first(['date', 'name']);

        if ($holiday) {
            $data['upcoming_holiday'] = [
                'date' => $holiday->date,
                'name' => $holiday->name,
            ];
        }

        return $data;
    }

    public function selectModule(string $moduleKey): void
    {
        $selectedModule = $this->getModules()->firstWhere('key', $moduleKey);
        abort_if(!$selectedModule, 403);
        ModuleAccess::setActive($selectedModule['key']);

        $routeName = $selectedModule['route'] ?? null;
        if ($routeName && app('router')->has($routeName)) {
            $this->redirect(route($routeName));
            return;
        }

        $this->redirect(filament()->getCurrentPanel()->getUrl());
    }
}