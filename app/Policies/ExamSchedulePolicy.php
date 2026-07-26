<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ExamSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExamSchedule');
    }

    public function view(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('View:ExamSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExamSchedule');
    }

    public function update(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('Update:ExamSchedule');
    }

    public function delete(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('Delete:ExamSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExamSchedule');
    }

    public function restore(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('Restore:ExamSchedule');
    }

    public function forceDelete(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('ForceDelete:ExamSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExamSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExamSchedule');
    }

    public function replicate(AuthUser $authUser, ExamSchedule $examSchedule): bool
    {
        return $authUser->can('Replicate:ExamSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExamSchedule');
    }

}