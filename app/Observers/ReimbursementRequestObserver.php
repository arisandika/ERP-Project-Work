<?php

namespace App\Observers;

use App\Models\HR\ReimbursementRequest;
use App\Models\User;
use App\Notifications\ReimbursementAdminNotification;
use App\Notifications\ReimbursementEmployeeNotification;

class ReimbursementRequestObserver
{
    /**
     * Saat reimburse dibuat
     */
    public function created(ReimbursementRequest $reimbursement): void
    {
        $employee = $reimbursement->employee;

        // Ambil semua admin / manager / super admin
        $admins = User::whereHas('roles', fn ($q) =>
            $q->whereIn('name', ['admin', 'manager', 'super_admin'])
        )->get();

        foreach ($admins as $admin) {
            $admin->notify(new ReimbursementAdminNotification($reimbursement));
        }
    }

    /**
     * Saat reimburse diupdate
     */
    public function updated(ReimbursementRequest $reimbursement): void
    {
        // Cek apakah status berubah
        if ($reimbursement->wasChanged('status')) {

            if (in_array($reimbursement->status, ['approved', 'rejected'])) {

                $employeeUser = $reimbursement->employee?->user;

                if ($employeeUser) {
                    $employeeUser->notify(
                        new ReimbursementEmployeeNotification($reimbursement)
                    );
                }
            }
        }
    }
}