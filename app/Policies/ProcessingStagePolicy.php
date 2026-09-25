<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProcessingStage;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProcessingStagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcessingStage');
    }

    public function view(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('View:ProcessingStage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcessingStage');
    }

    public function update(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('Update:ProcessingStage');
    }

    public function delete(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('Delete:ProcessingStage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProcessingStage');
    }

    public function restore(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('Restore:ProcessingStage');
    }

    public function forceDelete(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('ForceDelete:ProcessingStage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcessingStage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcessingStage');
    }

    public function replicate(AuthUser $authUser, ProcessingStage $processingStage): bool
    {
        return $authUser->can('Replicate:ProcessingStage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcessingStage');
    }
}
