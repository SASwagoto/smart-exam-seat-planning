<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AcademicSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AcademicSession');
    }

    public function view(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('View:AcademicSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AcademicSession');
    }

    public function update(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('Update:AcademicSession');
    }

    public function delete(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('Delete:AcademicSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AcademicSession');
    }

    public function restore(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('Restore:AcademicSession');
    }

    public function forceDelete(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('ForceDelete:AcademicSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AcademicSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AcademicSession');
    }

    public function replicate(AuthUser $authUser, AcademicSession $academicSession): bool
    {
        return $authUser->can('Replicate:AcademicSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AcademicSession');
    }

}