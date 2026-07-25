<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SectionCourseAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionCourseAssignmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SectionCourseAssignment');
    }

    public function view(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('View:SectionCourseAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SectionCourseAssignment');
    }

    public function update(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('Update:SectionCourseAssignment');
    }

    public function delete(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('Delete:SectionCourseAssignment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SectionCourseAssignment');
    }

    public function restore(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('Restore:SectionCourseAssignment');
    }

    public function forceDelete(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('ForceDelete:SectionCourseAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SectionCourseAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SectionCourseAssignment');
    }

    public function replicate(AuthUser $authUser, SectionCourseAssignment $sectionCourseAssignment): bool
    {
        return $authUser->can('Replicate:SectionCourseAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SectionCourseAssignment');
    }

}