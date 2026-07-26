<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ExamSlot;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamSlotPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExamSlot');
    }

    public function view(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('View:ExamSlot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExamSlot');
    }

    public function update(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('Update:ExamSlot');
    }

    public function delete(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('Delete:ExamSlot');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExamSlot');
    }

    public function restore(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('Restore:ExamSlot');
    }

    public function forceDelete(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('ForceDelete:ExamSlot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExamSlot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExamSlot');
    }

    public function replicate(AuthUser $authUser, ExamSlot $examSlot): bool
    {
        return $authUser->can('Replicate:ExamSlot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExamSlot');
    }

}