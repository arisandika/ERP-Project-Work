<?php
namespace App\Filament\Pages\HR;

use App\Models\HR\Attendance as AttendanceModel;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Attendance extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static string $view = 'filament.pages.hr.attendance';

    protected static ?string $slug = 'attendance';

    protected static string $routePath = 'attendance';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?string $navigationLabel = 'Presensi Harian';

    protected static ?string $title = 'Presensi Harian';

    protected ?string $subheading = 'Catat presensi harian Anda di sini';

    public $employee, $shift, $office, $note, $latitude, $longitude, $timestamp;

    public $attendanceToday, $hasCheckedIn = false, $hasCheckedOut = false;

    public function mount()
    {
        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki presensi harian');
        }

        $user           = Auth::user();
        $this->employee = $user?->employee;

        if ($this->employee) {
            $this->office = $this->employee->office;
            $this->shift  = $this->employee->shift;

            $today = now()->toDateString();

            $attendance = AttendanceModel::where('employee_id', $this->employee->id)
                ->whereDate('date', $today)
                ->first();

            $this->attendanceToday = $attendance;

            $this->hasCheckedIn  = $attendance && $attendance->clock_in;
            $this->hasCheckedOut = $attendance && $attendance->clock_out;
        }

        $this->timestamp = now()->toDateTimeString();
    }
}
