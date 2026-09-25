<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoutingHistory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RoutingHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RoutingHistory');
    }

    public function view(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('View:RoutingHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RoutingHistory');
    }

    public function update(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('Update:RoutingHistory');
    }

    public function delete(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('Delete:RoutingHistory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RoutingHistory');
    }

    public function restore(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('Restore:RoutingHistory');
    }

    public function forceDelete(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('ForceDelete:RoutingHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RoutingHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RoutingHistory');
    }

    public function replicate(AuthUser $authUser, RoutingHistory $routingHistory): bool
    {
        return $authUser->can('Replicate:RoutingHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RoutingHistory');
    }
}
