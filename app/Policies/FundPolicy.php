<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fund;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FundPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Fund');
    }

    public function view(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('View:Fund');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Fund');
    }

    public function update(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('Update:Fund');
    }

    public function delete(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('Delete:Fund');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Fund');
    }

    public function restore(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('Restore:Fund');
    }

    public function forceDelete(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('ForceDelete:Fund');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Fund');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Fund');
    }

    public function replicate(AuthUser $authUser, Fund $fund): bool
    {
        return $authUser->can('Replicate:Fund');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Fund');
    }
}
