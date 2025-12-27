<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class AttendanceLeaveListWidget extends Widget
{
    // use HasPageShield;

    protected static string $view = 'filament.widgets.hr.attendance-leave-list-widget';

    protected static ?string $pollingInterval = '30s';

    public ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    public ?array $selectedEmployee = null;
    
    public bool $isShowingEmployeeDetailModal = false; 

    protected $listeners = ['showEmployeeDetail' => 'loadEmployeeDetail']; 

    public function getTodayLeavesProperty()
    {
        $today = Carbon::today();

        return LeaveRequest::with(['employee.department', 'employee.office', 'employee.shift', 'leave'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get();
    }

    public function showEmployeeDetail($employeeId): void
    {
        $employee = Employee::with(['department', 'office', 'shift'])->find($employeeId);

        if (! $employee) {
            $this->selectedEmployee = null;
            $this->isShowingEmployeeDetailModal = false; 
            return;
        }

        $this->selectedEmployee = [
            'id'               => $employee->id,
            'name'             => $employee->full_name,
            'position'         => $employee->position,
            'department'       => $employee->department->name ?? '-',
            'office'           => $employee->office->name ?? '-',
            'shift'            => [
                'name'  => $employee->shift->name ?? '-',
                'start' => $employee->shift->start_time ?? null,
                'end'   => $employee->shift->end_time ?? null,
            ],
            'photo'            => $employee->photo ? asset('storage/' . $employee->photo) : url('/assets/placeholder.jpg'),
            'can_wfa'          => $employee->can_wfa,
            'can_unlock_shift' => $employee->can_unlock_shift,
        ];

        $this->isShowingEmployeeDetailModal = true; 
    }
    
    public function closeEmployeeDetailModal(): void
    {
        $this->isShowingEmployeeDetailModal = false; 
        $this->selectedEmployee = null; 
    }
}