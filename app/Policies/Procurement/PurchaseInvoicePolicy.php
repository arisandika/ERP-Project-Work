<?php

namespace App\Policies\Procurement;

use App\Models\User;
use App\Models\Procurement\PurchaseInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseInvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('view_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('update_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('delete_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('force_delete_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('restore_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->can('replicate_procurement::purchase::invoice');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_procurement::purchase::invoice');
    }
}
