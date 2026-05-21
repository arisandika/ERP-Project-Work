<?php

namespace App\Policies\AfterSales;

use App\Models\AfterSales\ReturnRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReturnRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Super Admin bypass.
     * Metode ini akan dieksekusi pertama kali sebelum metode lain.
     */
    public function before(User $user, $ability): ?bool
    {
        // Sesuaikan dengan penamaan role Super Admin Anda
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null; // Lanjut ke pengecekan spesifik di bawah jika bukan super_admin
    }

    /**
     * Determine whether the user can view any models.
     * Siapa saja yang boleh melihat daftar retur?
     */
    public function viewAny(User $user): bool
    {
        // Berikan akses jika user memiliki salah satu dari role terkait After-Sales
        return $user->hasAnyRole(['super_admin', 'inventory_employees', 'technician', 'procurement_employees']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->hasAnyRole(['super_admin', 'inventory_employees', 'technician', 'procurement_employees']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('inventory_employees');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReturnRequest $returnRequest): bool
    {
        if ($user->hasRole('inventory_employees') && $returnRequest->status === ReturnRequest::STATUS_RECEIVED) {
            return true;
        }

        // Teknisi dan Purchasing selalu bisa update selama mereka mengakses menu mereka
        if ($user->hasAnyRole(['super_admin', 'technician', 'procurement_employees'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Dalam ERP, penghapusan data transaksi sangat dilarang untuk menjaga audit trail.
     */
    public function delete(User $user, ReturnRequest $returnRequest): bool
    {
        return false;
    }
}
