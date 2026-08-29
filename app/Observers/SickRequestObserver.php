<?php
namespace App\Observers;

use App\Models\HR\SickRequest;
use App\Models\User;
use App\Notifications\SickRequestAdminNotification;
use App\Notifications\SickRequestEmployeeNotification;
use Illuminate\Support\Facades\Log;

class SickRequestObserver
{
    public function created(SickRequest $sickRequest): void
    {
        $employee = $sickRequest->employee;

        // Kirim notifikasi ke ADMIN
        $admins = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['admin', 'manager', 'super_admin'])
        )->get();

        foreach ($admins as $admin) {
            $admin->notify(new SickRequestAdminNotification($sickRequest));
            SickRequestAdminNotification::sendFilamentNotification($sickRequest, $admin);
        }

        Log::info("Admin notified about new sick request: {$employee?->full_name}");
    }

    public function updated(SickRequest $sickRequest): void
    {
        // Notifikasi hanya ketika STATUS berubah (approved/rejected)
        if ($sickRequest->wasChanged('status')) {
            $employee = $sickRequest->employee?->user;

            if ($employee) {
                $employee->notify(new SickRequestEmployeeNotification($sickRequest));
                SickRequestEmployeeNotification::sendFilamentNotification($sickRequest, $employee);
            }

            Log::info("Employee notified: Sick request status updated");
        }
    }
}
