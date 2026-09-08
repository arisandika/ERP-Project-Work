<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class AttendanceLeaveListWidget extends Widget
{
    protected static string $view = 'filament.widgets.hr.attendance-leave-list-widget';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 1,
    ];

    public ?array $selectedEmployee = null;

    public bool $isShowingEmployeeDetailModal = false;

    protected $listeners = ['showEmployeeDetail' => 'loadEmployeeDetail'];

    public function getTodayLeavesProperty()
    {
        $today = Carbon::today();

        return LeaveRequest::with([
            'employee.department',
            'employee.office',
            'employee.shift',
            'leave'
        ])
            ->where('status', 'approved')
            ->whereDoesntHave(
                'employee.user.roles',
                fn ($query) => $query->where('name', 'super_admin')
            )
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($leaveRequest) {
                $employee = $leaveRequest->employee;

                // Durasi cuti hari kerja
                $totalDays = $leaveRequest->start_date->diffInDaysFiltered(
                    fn($date) => !$date->isWeekend(),
                    $leaveRequest->end_date
                ) + 1;

                return [
                    'id' => $leaveRequest->id,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->department->name ?? '-',
                    'office' => $employee->office->name ?? '-',
                    'shift' => $employee->shift->name ?? '-',
                    'leave_type' => $leaveRequest->leave->leave_type ?? '-',
                    'start_date' => $leaveRequest->start_date->format('d M'),
                    'end_date' => $leaveRequest->end_date->format('d M Y'),
                    'total_days' => $totalDays,
                    'status' => $leaveRequest->status,
                ];
            });
    }

    public function showEmployeeDetail($employeeId): void
    {
        $employee = Employee::forAttendanceReporting()->with([
            'department',
            'office',
            'shift',
            'leaveRequests.leave'
        ])->find($employeeId);

        if (!$employee) {
            $this->selectedEmployee = null;
            $this->isShowingEmployeeDetailModal = false;
            return;
        }

        // Hitung cuti yang sedang berlaku hari ini
        $today = Carbon::today();
        $currentLeaves = $employee
            ->leaveRequests()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->map(function ($leaveRequest) {
                $totalDays = $leaveRequest->start_date->diffInDaysFiltered(
                    fn($date) => !$date->isWeekend(),
                    $leaveRequest->end_date
                ) + 1;

                return [
                    'leave_type' => $leaveRequest->leave->leave_type ?? '-',
                    'start_date' => $leaveRequest->start_date->format('d M Y'),
                    'end_date' => $leaveRequest->end_date->format('d M Y'),
                    'total_days' => $totalDays,
                ];
            });

        $this->selectedEmployee = [
            'id' => $employee->id,
            'name' => $employee->full_name,
            'position' => $employee->position ?? '-',
            'department' => $employee->department->name ?? '-',
            'office' => $employee->office->name ?? '-',
            'shift' => [
                'name' => $employee->shift->name ?? '-',
                'start' => $employee->shift->start_time ?? null,
                'end' => $employee->shift->end_time ?? null,
            ],
            'photo' => $employee->photo ? Storage::url($employee->photo) : asset('assets/placeholder.jpg'),
            'can_wfa' => $employee->can_wfa,
            'can_unlock_shift' => $employee->can_unlock_shift,
            'current_leaves' => $currentLeaves,
        ];

        $this->isShowingEmployeeDetailModal = true;
    }

    public function closeEmployeeDetailModal(): void
    {
        $this->isShowingEmployeeDetailModal = false;
        $this->selectedEmployee = null;
    }
}
