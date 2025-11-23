<?php

namespace App\Observers;

use App\Models\HR\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestAdminNotification;
use App\Notifications\LeaveRequestEmployeeNotification;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    public function created(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        // Kirim notifikasi ke ADMIN
        $admins = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['admin', 'manager', 'super_admin'])
        )->get();

        foreach ($admins as $admin) {
            $admin->notify(new LeaveRequestAdminNotification($leaveRequest));
            LeaveRequestAdminNotification::sendFilamentNotification($leaveRequest, $admin);
        }

        Log::info("Admin notified about new leave request: {$employee?->full_name}");
    }

    public function updated(LeaveRequest $leaveRequest): void
    {
        // Notifikasi hanya ketika STATUS berubah (approved/rejected)
        if ($leaveRequest->wasChanged('status')) {
            $employee = $leaveRequest->employee?->user;

            if ($employee) {
                $employee->notify(new LeaveRequestEmployeeNotification($leaveRequest));
                LeaveRequestEmployeeNotification::sendFilamentNotification($leaveRequest, $employee);
            }

            Log::info("Employee notified: Leave request status updated");
        }
    }
}
