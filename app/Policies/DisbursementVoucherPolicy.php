<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DisbursementVoucher;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DisbursementVoucherPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DisbursementVoucher');
    }

    public function view(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('View:DisbursementVoucher');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DisbursementVoucher');
    }

    public function update(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('Update:DisbursementVoucher');
    }

    public function delete(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('Delete:DisbursementVoucher');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DisbursementVoucher');
    }

    public function restore(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('Restore:DisbursementVoucher');
    }

    public function forceDelete(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('ForceDelete:DisbursementVoucher');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DisbursementVoucher');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DisbursementVoucher');
    }

    public function replicate(AuthUser $authUser, DisbursementVoucher $disbursementVoucher): bool
    {
        return $authUser->can('Replicate:DisbursementVoucher');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DisbursementVoucher');
    }
}
